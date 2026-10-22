# The storage in a Lambda with the layers of PHP and imagick of Bref behind its function URL (ADR 0054, 0055, 0063,
# 0068):
# build/storage.zip, packaged by make package-storage, redeployed when the version of its code changes. The layers
# are those of docker/php and docker/lambda (bref/php-85:3.0.10 = layer php-85:21, the image
# bref/extra-imagick-php-85 of their digest = layer imagick-php-85:4): a new version of Bref changes the three.
locals {
  name          = "prospecter-${var.instance}-storage"
  bref_php      = "arn:aws:lambda:${var.region}:873528684822:layer:php-85:21"
  bref_imagick  = "arn:aws:lambda:${var.region}:403367587399:layer:imagick-php-85:4"
  package       = "${path.module}/../../../build/storage.zip"
  ssm_path      = "/prospecter/${var.instance}/storage"
  ssm_arn       = "arn:aws:ssm:${var.region}:${var.aws_account_id}:parameter${local.ssm_path}"
  logs_retained = 30
}

resource "aws_iam_role" "lambda" {
  name                 = "${local.name}-lambda"
  description          = "The function of the storage"
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
  name = "the-files-of-the-storage"
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
        # the requests it signs for the browsers act with these rights too (ADR 0077)
        Sid      = "KeepItsFiles"
        Effect   = "Allow"
        Action   = ["s3:DeleteObject", "s3:GetObject", "s3:PutObject"]
        Resource = ["${aws_s3_bucket.files.arn}/*", "${aws_s3_bucket.private_files.arn}/*"]
      },
      {
        # a missing key is then NoSuchKey, not AccessDenied
        Sid      = "KnowWhatItsBucketsHold"
        Effect   = "Allow"
        Action   = "s3:ListBucket"
        Resource = [aws_s3_bucket.files.arn, aws_s3_bucket.private_files.arn]
      },
      {
        Sid      = "ReadItsParameters"
        Effect   = "Allow"
        Action   = ["ssm:GetParameter", "ssm:GetParameters"]
        Resource = "${local.ssm_arn}/*"
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

resource "aws_lambda_function" "storage" {
  function_name    = local.name
  role             = aws_iam_role.lambda.arn
  runtime          = "provided.al2023"
  handler          = "public/index.php"
  layers           = [local.bref_php, local.bref_imagick]
  memory_size      = 1024
  timeout          = 28
  filename         = local.package
  source_code_hash = base64sha256(var.code_version)

  environment {
    variables = {
      BREF_RUNTIME          = "fpm"
      BREF_BINARY_RESPONSES = "1"
      APP_ENV               = "prod"
      APP_SECRET            = "bref-ssm:${aws_ssm_parameter.app_secret.name}"
      APP_SHARE_DIR         = "/tmp/share"
      # read at the start, the distribution calling the function (a cycle in its variables)
      DEFAULT_URI        = "bref-ssm:${local.ssm_path}/public-url"
      STORAGE_PUBLIC_URL = "bref-ssm:${local.ssm_path}/public-url"
      STORAGE_TOKEN      = "bref-ssm:${aws_ssm_parameter.token.name}"
      S3_ENDPOINT        = ""
      S3_BUCKET          = aws_s3_bucket.files.id
      S3_PRIVATE_BUCKET  = aws_s3_bucket.private_files.id
      # the browsers reach the private files on S3 itself
      S3_BROWSER_ENDPOINT = ""
      # read at the start too, for the same cycle
      CLOUDFRONT_DISTRIBUTION_ID = "bref-ssm:${local.ssm_path}/distribution-id"
    }
  }

  depends_on = [aws_cloudwatch_log_group.lambda, aws_iam_role_policy.lambda]
}

# A removed file is invalidated in CloudFront (ADR 0070): apart from the policy the function waits for, the
# distribution calling the function
resource "aws_iam_role_policy" "invalidate" {
  name = "forget-the-removed-files"
  role = aws_iam_role.lambda.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid      = "InvalidateItsDistribution"
      Effect   = "Allow"
      Action   = "cloudfront:CreateInvalidation"
      Resource = aws_cloudfront_distribution.files.arn
    }]
  })
}
