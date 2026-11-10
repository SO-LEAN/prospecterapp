# The events of the commits leave the tables for the bus (ADR 0098): each command writes its events in an item of
# outbox of the table of its context, whose stream gives the new items to the relay, a function of the same package
# publishing them on the bus of the instance. Nothing reads the bus yet: the delivery stays sync until the
# subscriptions are deployed.
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
