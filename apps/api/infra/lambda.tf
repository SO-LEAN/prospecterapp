# The api in a Lambda with the PHP layer of Bref behind an HTTP API (ADR 0053, 0055, 0063): build/api.zip, packaged
# by make package-api, redeployed when the version of its code changes. The runtime of the layer is the one of
# docker/php and docker/lambda (bref/php-85:3.0.9 = layer php-85:20): a new version of Bref changes the three.
locals {
  name          = "prospecter-${var.instance}-api"
  bref_php      = "arn:aws:lambda:${var.region}:873528684822:layer:php-85:20"
  package       = "${path.module}/../../../build/api.zip"
  ssm_path      = "/prospecter/${var.instance}/api"
  ssm_arn       = "arn:aws:ssm:${var.region}:${var.aws_account_id}:parameter${local.ssm_path}"
  api_host      = local.edge_domains.api != "" ? local.edge_domains.api : trimprefix(aws_apigatewayv2_api.api.api_endpoint, "https://")
  table_arns    = [for table in aws_dynamodb_table.context : table.arn]
  logs_retained = 30
}

resource "aws_iam_role" "lambda" {
  name                 = "${local.name}-lambda"
  description          = "The function of the api"
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

resource "aws_iam_role_policy" "lambda" {
  name = "the-data-of-the-api"
  role = aws_iam_role.lambda.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid      = "WriteItsLogs"
        Effect   = "Allow"
        Action   = ["logs:CreateLogStream", "logs:PutLogEvents"]
        Resource = "${aws_cloudwatch_log_group.lambda.arn}:*"
      },
      {
        Sid    = "UseItsTables"
        Effect = "Allow"
        Action = [
          "dynamodb:BatchGetItem", "dynamodb:BatchWriteItem", "dynamodb:ConditionCheckItem", "dynamodb:DeleteItem",
          "dynamodb:DescribeTable", "dynamodb:GetItem", "dynamodb:PutItem", "dynamodb:Query", "dynamodb:Scan",
          "dynamodb:UpdateItem",
        ]
        Resource = concat(local.table_arns, [for arn in local.table_arns : "${arn}/index/*"])
      },
      {
        Sid      = "FindItsUserPool"
        Effect   = "Allow"
        Action   = "cognito-idp:ListUserPools"
        Resource = "*"
      },
      {
        Sid    = "AdministerItsMembers"
        Effect = "Allow"
        Action = [
          "cognito-idp:AdminCreateUser", "cognito-idp:AdminGetUser", "cognito-idp:AdminSetUserPassword",
          "cognito-idp:AdminUpdateUserAttributes", "cognito-idp:ListUserPoolClients", "cognito-idp:ListUsers",
        ]
        Resource = aws_cognito_user_pool.members.arn
      },
      {
        Sid    = "ReadItsParameters"
        Effect = "Allow"
        Action = ["ssm:GetParameter", "ssm:GetParameters"]
        Resource = [
          "${local.ssm_arn}/*",
          "arn:aws:ssm:${var.region}:${var.aws_account_id}:parameter${local.storage_ssm_path}/token",
          "arn:aws:ssm:${var.region}:${var.aws_account_id}:parameter${local.origin_secret_parameter}",
        ]
      },
      {
        Sid       = "DecryptItsSecretParameters"
        Effect    = "Allow"
        Action    = "kms:Decrypt"
        Resource  = "*"
        Condition = { StringEquals = { "kms:ViaService" = "ssm.${var.region}.amazonaws.com" } }
      },
    ]
  })
}

resource "aws_cloudwatch_log_group" "lambda" {
  name              = "/aws/lambda/${local.name}"
  retention_in_days = local.logs_retained
}

resource "aws_lambda_function" "api" {
  function_name    = local.name
  role             = aws_iam_role.lambda.arn
  runtime          = "provided.al2023"
  handler          = "public/index.php"
  layers           = [local.bref_php]
  memory_size      = 1024
  timeout          = 28
  filename         = local.package
  source_code_hash = base64sha256(var.code_version)

  environment {
    variables = {
      BREF_RUNTIME            = "fpm"
      APP_ENV                 = "prod"
      APP_SECRET              = "bref-ssm:${aws_ssm_parameter.app_secret.name}"
      APP_SHARE_DIR           = "/tmp/share"
      APP_API_HOST            = local.api_host
      DEFAULT_URI             = "https://${local.api_host}"
      TRUSTED_PROXIES         = "REMOTE_ADDR"
      ORIGIN_SECRET           = local.has_origin_secret ? "bref-ssm:${local.origin_secret_parameter}" : ""
      CORS_ALLOW_ORIGIN       = local.cors_allow_origin
      DYNAMODB_ENDPOINT       = ""
      DYNAMODB_TABLE_PREFIX   = local.name
      COGNITO_ENDPOINT        = ""
      COGNITO_USER_POOL_NAME  = aws_cognito_user_pool.members.name
      COGNITO_KEYS_URL        = "https://cognito-idp.${var.region}.amazonaws.com/%s/.well-known/jwks.json"
      COGNITO_PUBLIC_ENDPOINT = ""
      STORAGE_PUBLIC_URL      = local.storage_public_url
      STORAGE_INTERNAL_URL    = data.aws_ssm_parameter.storage_internal_url.insecure_value
      STORAGE_TOKEN           = "bref-ssm:${local.storage_ssm_path}/token"
    }
  }

  depends_on = [aws_cloudwatch_log_group.lambda, aws_iam_role_policy.lambda]
}
