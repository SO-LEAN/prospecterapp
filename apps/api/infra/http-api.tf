# The HTTP API in front of the function: on the domain the edge gives (ADR 0067), its AWS address closed, else on
# its AWS address
resource "aws_apigatewayv2_api" "api" {
  name                         = local.name
  protocol_type                = "HTTP"
  disable_execute_api_endpoint = local.edge_domains.api != ""
}

resource "aws_apigatewayv2_integration" "api" {
  api_id                 = aws_apigatewayv2_api.api.id
  integration_type       = "AWS_PROXY"
  integration_uri        = aws_lambda_function.api.invoke_arn
  payload_format_version = "2.0"
}

resource "aws_apigatewayv2_route" "everything" {
  api_id    = aws_apigatewayv2_api.api.id
  route_key = "$default"
  target    = "integrations/${aws_apigatewayv2_integration.api.id}"
}

resource "aws_apigatewayv2_stage" "default" {
  api_id      = aws_apigatewayv2_api.api.id
  name        = "$default"
  auto_deploy = true
}

resource "aws_lambda_permission" "http_api" {
  statement_id  = "TheHttpApiInvokes"
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.api.function_name
  principal     = "apigateway.amazonaws.com"
  source_arn    = "${aws_apigatewayv2_api.api.execution_arn}/*"
}

resource "aws_apigatewayv2_domain_name" "edge" {
  count       = local.edge_domains.api != "" ? 1 : 0
  domain_name = local.edge_domains.api

  domain_name_configuration {
    certificate_arn = local.edge_certificate
    endpoint_type   = "REGIONAL"
    security_policy = "TLS_1_2"
  }
}

resource "aws_apigatewayv2_api_mapping" "edge" {
  count       = local.edge_domains.api != "" ? 1 : 0
  api_id      = aws_apigatewayv2_api.api.id
  domain_name = aws_apigatewayv2_domain_name.edge[0].id
  stage       = aws_apigatewayv2_stage.default.id
}
