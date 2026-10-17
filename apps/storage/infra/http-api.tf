# The HTTP API in front of the function: the internal entry of the services of the platform (POST /files, DELETE
# /files/<key>, with their token) and the origin CloudFront fails over to for a missing size (cloudfront.tf)
resource "aws_apigatewayv2_api" "storage" {
  name          = local.name
  protocol_type = "HTTP"
}

resource "aws_apigatewayv2_integration" "storage" {
  api_id                 = aws_apigatewayv2_api.storage.id
  integration_type       = "AWS_PROXY"
  integration_uri        = aws_lambda_function.storage.invoke_arn
  payload_format_version = "2.0"
}

resource "aws_apigatewayv2_route" "everything" {
  api_id    = aws_apigatewayv2_api.storage.id
  route_key = "$default"
  target    = "integrations/${aws_apigatewayv2_integration.storage.id}"
}

resource "aws_apigatewayv2_stage" "default" {
  api_id      = aws_apigatewayv2_api.storage.id
  name        = "$default"
  auto_deploy = true
}

resource "aws_lambda_permission" "http_api" {
  statement_id  = "TheHttpApiInvokes"
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.storage.function_name
  principal     = "apigateway.amazonaws.com"
  source_arn    = "${aws_apigatewayv2_api.storage.execution_arn}/*"
}
