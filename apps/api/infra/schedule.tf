# The tasks run without a member at their origin (ADR 0147, 0154): a function of the package of the api, whose handler,
# a service of its container, runs the task its input names, each called by a schedule of its own; its commits publish
# what follows through their outbox, like those of the api. The contacts nobody attached in the 30 days after their last
# meeting are erased every hour (ADR 0146), the watches of the mailboxes of Gmail renewed every day, a watch lasting a
# week (ADR 0154).
locals {
  scheduled = "${local.name}-scheduled"
  scheduled_tasks = {
    erase-unattached    = "rate(1 hour)"
    renew-gmail-watches = "rate(1 day)"
  }
}

resource "aws_iam_role" "scheduled" {
  name                 = "${local.scheduled}-lambda"
  description          = "The function running the scheduled tasks"
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

resource "aws_iam_role_policy" "scheduled" {
  name = "run-the-scheduled-tasks"
  role = aws_iam_role.scheduled.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = concat([{
      Sid      = "WriteItsLogs"
      Effect   = "Allow"
      Action   = ["logs:CreateLogStream", "logs:PutLogEvents"]
      Resource = "${aws_cloudwatch_log_group.scheduled.arn}:*"
    }], local.data_statements)
  })
}

resource "aws_cloudwatch_log_group" "scheduled" {
  name              = "/aws/lambda/${local.scheduled}"
  retention_in_days = local.logs_retained
}

resource "aws_lambda_function" "scheduled" {
  function_name    = local.scheduled
  role             = aws_iam_role.scheduled.arn
  runtime          = "provided.al2023"
  handler          = "App\\Shared\\ScheduledTasksFunction"
  layers           = [local.bref_php]
  memory_size      = 1024
  timeout          = 300
  filename         = local.package
  source_code_hash = base64sha256(var.code_version)

  environment {
    variables = merge(local.environment, {
      BREF_RUNTIME = "function"
    })
  }

  depends_on = [aws_cloudwatch_log_group.scheduled, aws_iam_role_policy.scheduled]
}

resource "aws_iam_role" "scheduled_schedule" {
  name                 = "${local.scheduled}-schedule"
  description          = "The schedules calling the function running the scheduled tasks"
  permissions_boundary = var.permissions_boundary
  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect    = "Allow"
      Principal = { Service = "scheduler.amazonaws.com" }
      Action    = "sts:AssumeRole"
    }]
  })
}

resource "aws_iam_role_policy" "scheduled_schedule" {
  name = "call-the-scheduled-function"
  role = aws_iam_role.scheduled_schedule.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid      = "CallTheScheduledFunction"
      Effect   = "Allow"
      Action   = "lambda:InvokeFunction"
      Resource = aws_lambda_function.scheduled.arn
    }]
  })
}

# each task on its own schedule, its name in the input of the function; a run missed is made up by the next, which
# finds every contact whose day has passed, every watch to renew before its week ends
resource "aws_scheduler_schedule" "scheduled" {
  for_each            = local.scheduled_tasks
  name                = "${local.name}-${each.key}"
  schedule_expression = each.value

  flexible_time_window {
    mode = "OFF"
  }

  target {
    arn      = aws_lambda_function.scheduled.arn
    role_arn = aws_iam_role.scheduled_schedule.arn
    input    = jsonencode({ task = each.key })

    retry_policy {
      maximum_retry_attempts = 0
    }
  }
}
