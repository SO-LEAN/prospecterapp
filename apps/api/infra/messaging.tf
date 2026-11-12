# The events of the commits leave the tables for the bus (ADR 0098): each command writes its events in an item of
# outbox of the table of its context, whose stream gives the new items to the relay, a function of the same package
# publishing them on the bus of the instance, whose rules give them to the worker (below), the delivery of the api
# being outbox (ADR 0108, lambda.tf).
locals {
  relay = "${local.name}-relay"
  # only the insertions of the items of outbox wake the relay
  outbox_insertions = jsonencode({
    eventName = ["INSERT"]
    dynamodb  = { Keys = { PK = { S = [{ prefix = "OUTBOX#" }] } } }
  })
}

resource "aws_cloudwatch_event_bus" "integration" {
  name = "${local.name}-integration"
}

# what the relay failed to publish after its retries: the metadata of the batch only, the stream holding the records
# for 24 hours, the items of outbox for a week (app:outbox:republish)
resource "aws_sqs_queue" "relay_failures" {
  name                      = "${local.relay}-failures"
  message_retention_seconds = 1209600
  sqs_managed_sse_enabled   = true
}

resource "aws_iam_role" "relay" {
  name                 = "${local.relay}-lambda"
  description          = "The relay of the outbox of the api"
  permissions_boundary = var.permissions_boundary
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect    = "Allow"
      Principal = { Service = "lambda.amazonaws.com" }
      Action    = "sts:AssumeRole"
    }]
  })
}

resource "aws_iam_role_policy" "relay" {
  name = "relay-the-outbox"
  role = aws_iam_role.relay.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid      = "WriteItsLogs"
        Effect   = "Allow"
        Action   = ["logs:CreateLogStream", "logs:PutLogEvents"]
        Resource = "${aws_cloudwatch_log_group.relay.arn}:*"
      },
      {
        Sid      = "ReadTheStreamsOfTheTables"
        Effect   = "Allow"
        Action   = ["dynamodb:DescribeStream", "dynamodb:GetRecords", "dynamodb:GetShardIterator", "dynamodb:ListStreams"]
        Resource = [for table in aws_dynamodb_table.context : table.stream_arn]
      },
      {
        Sid      = "PublishOnTheBus"
        Effect   = "Allow"
        Action   = "events:PutEvents"
        Resource = aws_cloudwatch_event_bus.integration.arn
      },
      {
        Sid      = "KeepItsFailures"
        Effect   = "Allow"
        Action   = "sqs:SendMessage"
        Resource = aws_sqs_queue.relay_failures.arn
      },
    ]
  })
}

resource "aws_cloudwatch_log_group" "relay" {
  name              = "/aws/lambda/${local.relay}"
  retention_in_days = local.logs_retained
}

# the package of the api, its handler a service of its container (bref/symfony-bridge); the variables of .env do for
# the rest, the relay reaching neither the tables nor the user pool
resource "aws_lambda_function" "relay" {
  function_name    = local.relay
  role             = aws_iam_role.relay.arn
  runtime          = "provided.al2023"
  handler          = "App\\Shared\\Messaging\\OutboxRelay"
  layers           = [local.bref_php]
  memory_size      = 512
  timeout          = 28
  filename         = local.package
  source_code_hash = base64sha256(var.code_version)

  environment {
    variables = {
      BREF_RUNTIME          = "function"
      APP_ENV               = "prod"
      APP_SHARE_DIR         = "/tmp/share"
      EVENTBRIDGE_ENDPOINT  = ""
      INTEGRATION_EVENT_BUS = aws_cloudwatch_event_bus.integration.name
    }
  }

  depends_on = [aws_cloudwatch_log_group.relay, aws_iam_role_policy.relay]
}

# a batch failing is split in two until the failing record is alone, retried 5 times (a stream retries without end
# by default), then its metadata go to the queue of the failures; no record waits more than a day, under the 24
# hours the stream keeps it
resource "aws_lambda_event_source_mapping" "relay" {
  for_each = aws_dynamodb_table.context

  event_source_arn               = each.value.stream_arn
  function_name                  = aws_lambda_function.relay.arn
  starting_position              = "TRIM_HORIZON"
  batch_size                     = 10
  bisect_batch_on_function_error = true
  maximum_retry_attempts         = 5
  maximum_record_age_in_seconds  = 86400

  filter_criteria {
    filter {
      pattern = local.outbox_insertions
    }
  }

  destination_config {
    on_failure {
      destination_arn = aws_sqs_queue.relay_failures.arn
    }
  }
}

# The reactions run after the command, in a worker (ADR 0101): a rule of the bus per subscription of
# config/subscriptions.json invokes it, asynchronously, with the name of the subscription and the event; the worker
# applies it once (the inbox of the subscription) and keeps in the queue of the failures what it refuses, Lambda what
# it gave up after its retries, EventBridge what it could not deliver: app:integration:redrive gives them again.
locals {
  worker        = "${local.name}-worker"
  subscriptions = jsondecode(file("${path.module}/../config/subscriptions.json"))
}

# no consumer: what is there waits for app:integration:redrive, 14 days
resource "aws_sqs_queue" "integration_failures" {
  name                      = "${local.name}-integration-failures"
  message_retention_seconds = 1209600
  sqs_managed_sse_enabled   = true
}

# the rules put there what they could not deliver
resource "aws_sqs_queue_policy" "integration_failures" {
  queue_url = aws_sqs_queue.integration_failures.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid       = "KeepWhatTheRulesCouldNotDeliver"
      Effect    = "Allow"
      Principal = { Service = "events.amazonaws.com" }
      Action    = "sqs:SendMessage"
      Resource  = aws_sqs_queue.integration_failures.arn
      Condition = { ArnEquals = { "aws:SourceArn" = [for rule in aws_cloudwatch_event_rule.subscription : rule.arn] } }
    }]
  })
}

resource "aws_iam_role" "worker" {
  name                 = "${local.worker}-lambda"
  description          = "The worker of the api, running the reactions"
  permissions_boundary = var.permissions_boundary
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect    = "Allow"
      Principal = { Service = "lambda.amazonaws.com" }
      Action    = "sts:AssumeRole"
    }]
  })
}

# the data of the api, and the queue of the failures, where it puts what it refuses and Lambda what it gave up
resource "aws_iam_role_policy" "worker" {
  name = "react-to-the-events"
  role = aws_iam_role.worker.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = concat([
      {
        Sid      = "WriteItsLogs"
        Effect   = "Allow"
        Action   = ["logs:CreateLogStream", "logs:PutLogEvents"]
        Resource = "${aws_cloudwatch_log_group.worker.arn}:*"
      },
      {
        Sid      = "KeepItsFailures"
        Effect   = "Allow"
        Action   = ["sqs:GetQueueUrl", "sqs:SendMessage"]
        Resource = aws_sqs_queue.integration_failures.arn
      },
    ], local.data_statements)
  })
}

resource "aws_cloudwatch_log_group" "worker" {
  name              = "/aws/lambda/${local.worker}"
  retention_in_days = local.logs_retained
}

# the package and the environment of the api, its handler a service of its container; its reactions publish what
# they cause through the outbox of their commit
resource "aws_lambda_function" "worker" {
  function_name    = local.worker
  role             = aws_iam_role.worker.arn
  runtime          = "provided.al2023"
  handler          = "App\\Shared\\Messaging\\IntegrationEventConsumer"
  layers           = [local.bref_php]
  memory_size      = 1024
  timeout          = 28
  filename         = local.package
  source_code_hash = base64sha256(var.code_version)

  environment {
    variables = merge(local.environment, {
      BREF_RUNTIME                = "function"
      INTEGRATION_EVENTS_DELIVERY = "outbox"
      SQS_ENDPOINT                = ""
      INTEGRATION_FAILURES_QUEUE  = aws_sqs_queue.integration_failures.name
    })
  }

  depends_on = [aws_cloudwatch_log_group.worker, aws_iam_role_policy.worker]
}

# a failure retried twice, about a minute then two later; an event waits 6 hours at most; then the queue
resource "aws_lambda_function_event_invoke_config" "worker" {
  function_name                = aws_lambda_function.worker.function_name
  maximum_event_age_in_seconds = 21600
  maximum_retry_attempts       = 2

  destination_config {
    on_failure {
      destination = aws_sqs_queue.integration_failures.arn
    }
  }
}

resource "aws_cloudwatch_event_rule" "subscription" {
  for_each = local.subscriptions

  name           = each.key
  description    = "The subscription ${each.key}"
  event_bus_name = aws_cloudwatch_event_bus.integration.name
  event_pattern  = jsonencode({ source = each.value.sources, "detail-type" = each.value.events })
}

# the worker given the name of the subscription and the event; retried by EventBridge for a day when it cannot be
# invoked (throttled), then the queue
resource "aws_cloudwatch_event_target" "subscription" {
  for_each = local.subscriptions

  rule           = aws_cloudwatch_event_rule.subscription[each.key].name
  event_bus_name = aws_cloudwatch_event_bus.integration.name
  arn            = aws_lambda_function.worker.arn

  input_transformer {
    input_paths    = { source = "$.source", detailType = "$.detail-type", detail = "$.detail" }
    input_template = "{\"subscription\": \"${each.key}\", \"source\": <source>, \"detailType\": <detailType>, \"detail\": <detail>}"
  }

  retry_policy {
    maximum_event_age_in_seconds = 86400
    maximum_retry_attempts       = 185
  }

  dead_letter_config {
    arn = aws_sqs_queue.integration_failures.arn
  }
}

resource "aws_lambda_permission" "subscription" {
  for_each = local.subscriptions

  statement_id  = "subscription-${each.key}"
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.worker.function_name
  principal     = "events.amazonaws.com"
  source_arn    = aws_cloudwatch_event_rule.subscription[each.key].arn
}
