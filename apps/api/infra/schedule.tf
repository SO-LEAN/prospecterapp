# The contacts nobody attached in the 30 days after their last meeting are erased (ADR 0146): a schedule calls every
# hour a function of the package of the api, whose handler, a service of its container, gives each its erasure as a
# command (ADR 0147); its commits publish what follows through their outbox, like those of the api.
locals {
  unattached_eraser = "${local.name}-erase-unattached"
}

resource "aws_iam_role" "unattached_eraser" {
  name                 = "${local.unattached_eraser}-lambda"
  description          = "The function erasing the contacts left unattached"
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

resource "aws_iam_role_policy" "unattached_eraser" {
  name = "erase-the-contacts-left-unattached"
  role = aws_iam_role.unattached_eraser.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = concat([{
      Sid      = "WriteItsLogs"
      Effect   = "Allow"
      Action   = ["logs:CreateLogStream", "logs:PutLogEvents"]
      Resource = "${aws_cloudwatch_log_group.unattached_eraser.arn}:*"
    }], local.data_statements)
  })
}

resource "aws_cloudwatch_log_group" "unattached_eraser" {
  name              = "/aws/lambda/${local.unattached_eraser}"
  retention_in_days = local.logs_retained
}

resource "aws_lambda_function" "unattached_eraser" {
  function_name    = local.unattached_eraser
  role             = aws_iam_role.unattached_eraser.arn
  runtime          = "provided.al2023"
  handler          = "App\\Prospecting\\EraseContactsLeftUnattachedFunction"
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

  depends_on = [aws_cloudwatch_log_group.unattached_eraser, aws_iam_role_policy.unattached_eraser]
}

resource "aws_iam_role" "unattached_eraser_schedule" {
  name                 = "${local.unattached_eraser}-schedule"
  description          = "The schedule calling the function erasing the contacts left unattached"
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

resource "aws_iam_role_policy" "unattached_eraser_schedule" {
  name = "call-the-eraser"
  role = aws_iam_role.unattached_eraser_schedule.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid      = "CallTheEraser"
      Effect   = "Allow"
      Action   = "lambda:InvokeFunction"
      Resource = aws_lambda_function.unattached_eraser.arn
    }]
  })
}

# every hour: a contact is erased within the hour its day starts, on the clock of their last meeting; a run missed is
# made up by the next, which finds every contact whose day has passed
resource "aws_scheduler_schedule" "unattached_eraser" {
  name                = local.unattached_eraser
  schedule_expression = "rate(1 hour)"

  flexible_time_window {
    mode = "OFF"
  }

  target {
    arn      = aws_lambda_function.unattached_eraser.arn
    role_arn = aws_iam_role.unattached_eraser_schedule.arn

    retry_policy {
      maximum_retry_attempts = 0
    }
  }
}
