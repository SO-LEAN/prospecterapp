# The public address of the files (ADR 0051), on the AWS address of CloudFront until the domain of the platform
# (Cloudflare) comes: the bucket answers what exists, a size missing from it (403, 404) fails over to the function,
# which makes it once and puts it in the bucket, as nginx does locally. The function answers /storage/<size>/<key>.
locals {
  public_url = "https://${aws_cloudfront_distribution.files.domain_name}"
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

resource "aws_cloudfront_distribution" "files" {
  enabled         = true
  comment         = "The files of ${local.name}"
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
