# The alerts of the instance: a topic the alarms tell, the addresses of the instance subscribed to it by e-mail, each
# subscription waiting for its confirmation from the e-mail AWS sends (Terraform cannot confirm it). No key of KMS: the
# managed key of SNS refuses CloudWatch, and the messages hold no data (an alarm, a queue, a count).
resource "aws_sns_topic" "alerts" {
  name = "${local.name}-alerts"
}

# the addresses are sensitive, the plan of a pull request being a public log: only their number shows
resource "aws_sns_topic_subscription" "alert_email" {
  count = nonsensitive(length(var.alert_emails))

  topic_arn = aws_sns_topic.alerts.arn
  protocol  = "email"
  endpoint  = var.alert_emails[count.index]
}

# What another app tells (ADR 0059): the topic, read by path
resource "aws_ssm_parameter" "alerts_topic" {
  name  = "${local.ssm_path}/alerts-topic"
  type  = "String"
  value = aws_sns_topic.alerts.arn
}

# A message waiting in a queue of the failures is a reaction or a publication that will not happen by itself: told as
# soon as one is there, and again once the queue is empty
locals {
  failures = {
    relay = {
      queue = aws_sqs_queue.relay_failures.name
      reprise = join(" ", [
        "The relay failed to publish outboxes on the bus. Once the cause is fixed and deployed:",
        "make console INSTANCE=${var.instance} CMD=\"app:outbox:republish --since=<moment>\" (the outboxes are kept a week),",
        "then CMD=\"app:consistency:check\" to measure what is left.",
      ])
    }
    integration = {
      queue = aws_sqs_queue.integration_failures.name
      reprise = join(" ", [
        "Reactions failed or were refused. Once the cause is fixed and deployed:",
        "make console INSTANCE=${var.instance} CMD=\"app:integration:redrive\",",
        "then CMD=\"app:consistency:check\" to measure what is left.",
      ])
    }
  }
}

resource "aws_cloudwatch_metric_alarm" "failures" {
  for_each = local.failures

  alarm_name          = "${each.value.queue}-waiting"
  alarm_description   = each.value.reprise
  namespace           = "AWS/SQS"
  metric_name         = "ApproximateNumberOfMessagesVisible"
  dimensions          = { QueueName = each.value.queue }
  statistic           = "Maximum"
  period              = 300
  evaluation_periods  = 1
  comparison_operator = "GreaterThanThreshold"
  threshold           = 0
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.alerts.arn]
  ok_actions          = [aws_sns_topic.alerts.arn]
}

# A function failing or throttled, the HTTP API answering 5xx: told within 5 minutes, and again once it stops. A
# timeout is an error of its function: no alarm on the duration.
locals {
  functions = {
    api = {
      function = aws_lambda_function.api.function_name
      logs     = aws_cloudwatch_log_group.lambda.name
      errors   = var.function_errors_threshold
      reprise  = ""
    }
    # one failure of the daily run alerts, the next run being a day later
    scheduled = {
      function = aws_lambda_function.scheduled.function_name
      logs     = aws_cloudwatch_log_group.scheduled.name
      errors   = 0
      reprise  = local.scheduled_reprise
    }
    relay = {
      function = aws_lambda_function.relay.function_name
      logs     = aws_cloudwatch_log_group.relay.name
      errors   = var.function_errors_threshold
      reprise  = local.failures.relay.reprise
    }
    worker = {
      function = aws_lambda_function.worker.function_name
      logs     = aws_cloudwatch_log_group.worker.name
      errors   = var.function_errors_threshold
      reprise  = local.failures.integration.reprise
    }
  }
  scheduled_reprise = "Once the cause is fixed and deployed: make console INSTANCE=${var.instance} CMD=\"app:scheduled-tasks:run\"."
}

resource "aws_cloudwatch_metric_alarm" "function_errors" {
  for_each = local.functions

  alarm_name          = "${each.value.function}-errors"
  alarm_description   = trimspace("The function failed, its logs in ${each.value.logs}. ${each.value.reprise}")
  namespace           = "AWS/Lambda"
  metric_name         = "Errors"
  dimensions          = { FunctionName = each.value.function }
  statistic           = "Sum"
  period              = 300
  evaluation_periods  = 1
  comparison_operator = "GreaterThanThreshold"
  threshold           = each.value.errors
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.alerts.arn]
  ok_actions          = [aws_sns_topic.alerts.arn]
}

resource "aws_cloudwatch_metric_alarm" "function_throttles" {
  for_each = local.functions

  alarm_name          = "${each.value.function}-throttled"
  alarm_description   = trimspace("Invocations of the function were throttled, the executions of the account or its reservation exhausted, its logs in ${each.value.logs}. ${each.value.reprise}")
  namespace           = "AWS/Lambda"
  metric_name         = "Throttles"
  dimensions          = { FunctionName = each.value.function }
  statistic           = "Sum"
  period              = 300
  evaluation_periods  = 1
  comparison_operator = "GreaterThanThreshold"
  threshold           = var.function_throttles_threshold
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.alerts.arn]
  ok_actions          = [aws_sns_topic.alerts.arn]
}

# A task of the daily run failing fails not its invocation (it is logged, the others run): counted from its line of log,
# JSON on an instance, one failure alerting
resource "aws_cloudwatch_log_metric_filter" "scheduled_task_failed" {
  name           = "${local.scheduled}-task-failed"
  log_group_name = aws_cloudwatch_log_group.scheduled.name
  pattern        = "{ $.message = \"A scheduled task failed\" }"

  metric_transformation {
    name          = "ScheduledTaskFailures"
    namespace     = "Prospecter/${var.instance}/api"
    value         = "1"
    default_value = "0"
  }
}

resource "aws_cloudwatch_metric_alarm" "scheduled_task_failed" {
  alarm_name          = "${local.scheduled}-task-failed"
  alarm_description   = "A scheduled task failed, its name and its exception in ${aws_cloudwatch_log_group.scheduled.name}. ${local.scheduled_reprise}"
  namespace           = aws_cloudwatch_log_metric_filter.scheduled_task_failed.metric_transformation[0].namespace
  metric_name         = aws_cloudwatch_log_metric_filter.scheduled_task_failed.metric_transformation[0].name
  statistic           = "Sum"
  period              = 300
  evaluation_periods  = 1
  comparison_operator = "GreaterThanThreshold"
  threshold           = 0
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.alerts.arn]
  ok_actions          = [aws_sns_topic.alerts.arn]
}

resource "aws_cloudwatch_metric_alarm" "http_5xx" {
  alarm_name          = "${local.name}-5xx"
  alarm_description   = "The HTTP API answered 5xx, the logs of the api in ${aws_cloudwatch_log_group.lambda.name}."
  namespace           = "AWS/ApiGateway"
  metric_name         = "5xx"
  dimensions          = { ApiId = aws_apigatewayv2_api.api.id }
  statistic           = "Sum"
  period              = 300
  evaluation_periods  = 1
  comparison_operator = "GreaterThanThreshold"
  threshold           = var.http_5xx_threshold
  treat_missing_data  = "notBreaching"
  alarm_actions       = [aws_sns_topic.alerts.arn]
  ok_actions          = [aws_sns_topic.alerts.arn]
}
