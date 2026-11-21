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
