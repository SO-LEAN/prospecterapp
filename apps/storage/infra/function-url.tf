# The internal entry of the storage (ADR 0068): a function URL answering only the requests signed by a role allowed to
# call it, the api storing and removing the files with its role, CloudFront asking a missing size with its origin
# access control. Nothing reaches the function unsigned.
resource "aws_lambda_function_url" "storage" {
  function_name      = aws_lambda_function.storage.function_name
  authorization_type = "AWS_IAM"
}

resource "aws_cloudfront_origin_access_control" "function" {
  name                              = "${local.name}-function"
  description                       = "CloudFront calls the function of the storage for a missing size"
  origin_access_control_origin_type = "lambda"
  signing_behavior                  = "always"
  signing_protocol                  = "sigv4"
}

resource "aws_lambda_permission" "cloudfront_url" {
  statement_id           = "CloudFrontCallsTheUrl"
  action                 = "lambda:InvokeFunctionUrl"
  function_name          = aws_lambda_function.storage.function_name
  principal              = "cloudfront.amazonaws.com"
  source_arn             = aws_cloudfront_distribution.files.arn
  function_url_auth_type = "AWS_IAM"
}

# a function URL also needs the right to invoke the function itself
resource "aws_lambda_permission" "cloudfront_function" {
  statement_id  = "CloudFrontInvokesThroughTheUrl"
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.storage.function_name
  principal     = "cloudfront.amazonaws.com"
  source_arn    = aws_cloudfront_distribution.files.arn
}

locals {
  internal_url = trimsuffix(aws_lambda_function_url.storage.function_url, "/")
}
