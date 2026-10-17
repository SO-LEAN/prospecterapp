# The bucket of the files (ADR 0051): private, read by CloudFront only (cloudfront.tf), written by the function.
# A key never changes content; a removed or overwritten object stays 30 days as a former version, to recover a
# mistake. Its name ends with the account, bucket names being shared by every AWS account.
locals {
  bucket                = "${local.name}-files-${var.aws_account_id}"
  former_versions_kept  = 30
  abandoned_uploads_end = 7
}

resource "aws_s3_bucket" "files" {
  bucket = local.bucket
}

resource "aws_s3_bucket_public_access_block" "files" {
  bucket                  = aws_s3_bucket.files.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_versioning" "files" {
  bucket = aws_s3_bucket.files.id

  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_lifecycle_configuration" "files" {
  bucket = aws_s3_bucket.files.id

  rule {
    id     = "forget-former-versions"
    status = "Enabled"

    filter {}

    noncurrent_version_expiration {
      noncurrent_days = local.former_versions_kept
    }

    expiration {
      expired_object_delete_marker = true
    }

    abort_incomplete_multipart_upload {
      days_after_initiation = local.abandoned_uploads_end
    }
  }

  depends_on = [aws_s3_bucket_versioning.files]
}

resource "aws_s3_bucket_policy" "files" {
  bucket = aws_s3_bucket.files.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid       = "CloudFrontServesTheFiles"
        Effect    = "Allow"
        Principal = { Service = "cloudfront.amazonaws.com" }
        Action    = "s3:GetObject"
        Resource  = "${aws_s3_bucket.files.arn}/*"
        Condition = { StringEquals = { "AWS:SourceArn" = aws_cloudfront_distribution.files.arn } }
      },
      {
        Sid       = "OnlyThroughTls"
        Effect    = "Deny"
        Principal = "*"
        Action    = "s3:*"
        Resource  = [aws_s3_bucket.files.arn, "${aws_s3_bucket.files.arn}/*"]
        Condition = { Bool = { "aws:SecureTransport" = "false" } }
      },
    ]
  })

  depends_on = [aws_s3_bucket_public_access_block.files]
}
