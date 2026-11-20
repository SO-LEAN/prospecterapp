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

# The public booking pages, read without signing in (ADR 0151, 0153): a route of their own, its rate capped by the
# stage whatever the link and the address
resource "aws_apigatewayv2_route" "public_booking_page" {
  api_id    = aws_apigatewayv2_api.api.id
  route_key = "GET /api/booking-pages/{code}"
  target    = "integrations/${aws_apigatewayv2_integration.api.id}"
}

# A slot booked by a public link, without signing in (ADR 0164): a route of its own, its rate capped lower than the
# reading of the page
resource "aws_apigatewayv2_route" "public_booking" {
  api_id    = aws_apigatewayv2_api.api.id
  route_key = "POST /api/booking-pages/{code}/appointments"
  target    = "integrations/${aws_apigatewayv2_integration.api.id}"
}

# The facade signing the AIs in and the MCP server, public too (ADR 0156, 0157): a route each, its rate capped by the
# stage whatever the address
locals {
  oauth_routes = toset([
    "GET /.well-known/oauth-protected-resource",
    "GET /.well-known/oauth-protected-resource/mcp",
    "GET /.well-known/oauth-authorization-server",
    "GET /oauth/authorize",
    "GET /oauth/callback",
    "POST /oauth/token",
    "ANY /mcp",
  ])
}

resource "aws_apigatewayv2_route" "oauth" {
  for_each  = local.oauth_routes
  api_id    = aws_apigatewayv2_api.api.id
  route_key = each.value
  target    = "integrations/${aws_apigatewayv2_integration.api.id}"
}

resource "aws_apigatewayv2_stage" "default" {
  api_id      = aws_apigatewayv2_api.api.id
  name        = "$default"
  auto_deploy = true

  route_settings {
    route_key              = aws_apigatewayv2_route.public_booking_page.route_key
    throttling_rate_limit  = var.public_booking_rate_limit
    throttling_burst_limit = var.public_booking_burst_limit
  }

  route_settings {
    route_key              = aws_apigatewayv2_route.public_booking.route_key
    throttling_rate_limit  = var.public_booking_book_rate_limit
    throttling_burst_limit = var.public_booking_book_burst_limit
  }

  dynamic "route_settings" {
    for_each = aws_apigatewayv2_route.oauth
    content {
      route_key              = route_settings.value.route_key
      throttling_rate_limit  = var.oauth_rate_limit
      throttling_burst_limit = var.oauth_burst_limit
    }
  }
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
