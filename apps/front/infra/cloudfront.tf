# The address of the SPA, on the AWS address of CloudFront until the domain of the platform (Cloudflare) comes. Its
# function chooses the release (functions/release.js.tftpl): deploying a release is changing its version there.
locals {
  url = "https://${aws_cloudfront_distribution.site.domain_name}"
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
    version = local.release
    config  = jsonencode({ apiUrl = data.aws_ssm_parameter.api_url.insecure_value })
  })
}

resource "aws_cloudfront_distribution" "site" {
  enabled         = true
  comment         = "The SPA of ${local.name}"
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

  viewer_certificate {
    cloudfront_default_certificate = true
  }
}
