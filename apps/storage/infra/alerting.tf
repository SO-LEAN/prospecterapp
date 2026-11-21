# The topic of the alerts of the instance, published by the api (ADR 0059): in a new instance, none until the api is
# applied, the alarms of the storage telling nobody until its next apply (ADR 0069)
data "aws_ssm_parameters_by_path" "api" {
  path            = "/prospecter/${var.instance}/api"
  with_decryption = false
}

locals {
  api_parameters = {
    for index, name in data.aws_ssm_parameters_by_path.api.names :
    trimprefix(name, "/prospecter/${var.instance}/api/") => data.aws_ssm_parameters_by_path.api.values[index]
  }
  alerts_topic = compact([lookup(local.api_parameters, "alerts-topic", "")])
}

# The function failing or throttled, its URL answering 5xx: told within 5 minutes, and again once it stops. A timeout
# is an error of the function: no alarm on the duration.
resource "aws_cloudwatch_metric_alarm" "function_errors" {
  alarm_name          = "${local.name}-errors"
  alarm_description   = "The function of the storage failed, its logs in ${aws_cloudwatch_log_group.lambda.name}."
  namespace           = "AWS/Lambda"
  metric_name         = "Errors"
  dimensions          = { FunctionName = aws_lambda_function.storage.function_name }
  statistic           = "Sum"
  period              = 300
  evaluation_periods  = 1
  comparison_operator = "GreaterThanThreshold"
  threshold           = var.function_errors_threshold
  treat_missing_data  = "notBreaching"
  alarm_actions       = local.alerts_topic
  ok_actions          = local.alerts_topic
}

resource "aws_cloudwatch_metric_alarm" "function_throttles" {
  alarm_name          = "${local.name}-throttled"
  alarm_description   = "Invocations of the function of the storage were throttled, the executions of the account exhausted, its logs in ${aws_cloudwatch_log_group.lambda.name}."
  namespace           = "AWS/Lambda"
  metric_name         = "Throttles"
  dimensions          = { FunctionName = aws_lambda_function.storage.function_name }
  statistic           = "Sum"
  period              = 300
  evaluation_periods  = 1
  comparison_operator = "GreaterThanThreshold"
  threshold           = var.function_throttles_threshold
  treat_missing_data  = "notBreaching"
  alarm_actions       = local.alerts_topic
  ok_actions          = local.alerts_topic
}

# The storage has no HTTP API: its entry is its function URL (ADR 0068), whose answers 5xx Lambda counts
resource "aws_cloudwatch_metric_alarm" "http_5xx" {
  alarm_name          = "${local.name}-5xx"
  alarm_description   = "The function URL of the storage answered 5xx, its logs in ${aws_cloudwatch_log_group.lambda.name}."
  namespace           = "AWS/Lambda"
  metric_name         = "Url5xxCount"
  dimensions          = { FunctionName = aws_lambda_function.storage.function_name }
  statistic           = "Sum"
  period              = 300
  evaluation_periods  = 1
  comparison_operator = "GreaterThanThreshold"
  threshold           = var.http_5xx_threshold
  treat_missing_data  = "notBreaching"
  alarm_actions       = local.alerts_topic
  ok_actions          = local.alerts_topic
}
