# The address of the SPA: the domain the edge gives (ADR 0067), else the AWS address of CloudFront. Its function
# chooses the release (functions/release.js.tftpl): deploying a release is changing its version there.
locals {
  url     = local.edge_domains.front != "" ? "https://${local.edge_domains.front}" : "https://${aws_cloudfront_distribution.site.domain_name}"
  api_url = local.edge_domains.api != "" ? "https://${local.edge_domains.api}" : data.aws_ssm_parameter.api_url.insecure_value
  # the first 12 characters of the version of the code, as make names the release
  release = substr(var.code_version, 0, 12)
}

data "aws_cloudfront_cache_policy" "optimized" {
  name = "Managed-CachingOptimized"
}

data "aws_cloudfront_response_headers_policy" "security" {
  name = "Managed-SecurityHeadersPolicy"
}

resource "aws_cloudfront_origin_access_control" "site" {
  name                              = local.bucket
  description                       = "CloudFront reads the bucket of the SPA"
  origin_access_control_origin_type = "s3"
  signing_behavior                  = "always"
  signing_protocol                  = "sigv4"
}

resource "aws_cloudfront_function" "release" {
  name    = "${local.name}-release"
  comment = "The release of the SPA and the configuration of the instance"
  runtime = "cloudfront-js-2.0"
  publish = true
  code = templatefile("${path.module}/functions/release.js.tftpl", {
    version              = local.release
    config               = jsonencode({ apiUrl = local.api_url })
    origin_secret_digest = local.origin_secret_digest
  })
}

resource "aws_cloudfront_distribution" "site" {
  enabled         = true
  comment         = "The SPA of ${local.name}"
  aliases         = local.edge_domains.front != "" ? [local.edge_domains.front] : []
  price_class     = "PriceClass_100"
  http_version    = "http2and3"
  is_ipv6_enabled = true

  origin {
    origin_id                = "bucket"
    domain_name              = aws_s3_bucket.site.bucket_regional_domain_name
    origin_access_control_id = aws_cloudfront_origin_access_control.site.id
  }

  default_cache_behavior {
    target_origin_id           = "bucket"
    viewer_protocol_policy     = "redirect-to-https"
    allowed_methods            = ["GET", "HEAD"]
    cached_methods             = ["GET", "HEAD"]
    cache_policy_id            = data.aws_cloudfront_cache_policy.optimized.id
    response_headers_policy_id = data.aws_cloudfront_response_headers_policy.security.id
    compress                   = true

    function_association {
      event_type   = "viewer-request"
      function_arn = aws_cloudfront_function.release.arn
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
