# The public address of the files (ADR 0051): the domain the edge gives (ADR 0067), else the AWS address of
# CloudFront. The bucket answers what exists, a size missing from it (403, 404) fails over to the function,
# which makes it once and puts it in the bucket, as nginx does locally. The function answers /storage/<size>/<key>.
locals {
  public_url = local.edge_domains.storage != "" ? "https://${local.edge_domains.storage}" : "https://${aws_cloudfront_distribution.files.domain_name}"
}

data "aws_cloudfront_cache_policy" "optimized" {
  name = "Managed-CachingOptimized"
}

data "aws_cloudfront_response_headers_policy" "cors" {
  name = "Managed-SimpleCORS"
}

resource "aws_cloudfront_origin_access_control" "files" {
  name                              = local.bucket
  description                       = "CloudFront reads the bucket of the files"
  origin_access_control_origin_type = "s3"
  signing_behavior                  = "always"
  signing_protocol                  = "sigv4"
}

# Behind an edge, only the requests carrying its secret are answered (functions/only-through-the-edge.js.tftpl)
resource "aws_cloudfront_function" "only_through_the_edge" {
  name    = "${local.name}-only-through-the-edge"
  comment = "Only the requests the edge passes on"
  runtime = "cloudfront-js-2.0"
  publish = true
  code = templatefile("${path.module}/functions/only-through-the-edge.js.tftpl", {
    origin_secret_digest = local.origin_secret_digest
  })
}

resource "aws_cloudfront_distribution" "files" {
  enabled         = true
  comment         = "The files of ${local.name}"
  aliases         = local.edge_domains.storage != "" ? [local.edge_domains.storage] : []
  price_class     = "PriceClass_100"
  http_version    = "http2and3"
  is_ipv6_enabled = true

  origin {
    origin_id                = "bucket"
    domain_name              = aws_s3_bucket.files.bucket_regional_domain_name
    origin_access_control_id = aws_cloudfront_origin_access_control.files.id
  }

  origin {
    origin_id   = "function"
    domain_name = trimprefix(aws_apigatewayv2_api.storage.api_endpoint, "https://")
    origin_path = "/storage"

    custom_origin_config {
      http_port              = 80
      https_port             = 443
      origin_protocol_policy = "https-only"
      origin_ssl_protocols   = ["TLSv1.2"]
    }
  }

  origin_group {
    origin_id = "bucket-then-function"

    failover_criteria {
      status_codes = [403, 404]
    }

    member {
      origin_id = "bucket"
    }

    member {
      origin_id = "function"
    }
  }

  default_cache_behavior {
    target_origin_id           = "bucket-then-function"
    viewer_protocol_policy     = "redirect-to-https"
    allowed_methods            = ["GET", "HEAD"]
    cached_methods             = ["GET", "HEAD"]
    cache_policy_id            = data.aws_cloudfront_cache_policy.optimized.id
    response_headers_policy_id = data.aws_cloudfront_response_headers_policy.cors.id
    compress                   = true

    dynamic "function_association" {
      for_each = local.has_origin_secret ? [aws_cloudfront_function.only_through_the_edge.arn] : []
      content {
        event_type   = "viewer-request"
        function_arn = function_association.value
      }
    }
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  # the certificate of the domain the edge gives, else the one of CloudFront
  viewer_certificate {
    cloudfront_default_certificate = local.edge_certificate == ""
    acm_certificate_arn            = local.edge_certificate == "" ? null : local.edge_certificate
    ssl_support_method             = local.edge_certificate == "" ? null : "sni-only"
    minimum_protocol_version       = local.edge_certificate == "" ? "TLSv1" : "TLSv1.2_2021"
  }
}
