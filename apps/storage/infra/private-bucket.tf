# The bucket of the private files (ADR 0077): nothing serves it, the browsers send and read a file with the requests
# the function signs for them. No former version: a removed file is gone, it may hold personal data. An upload never
# kept goes after a day.
locals {
  private_bucket   = "${local.name}-private-files-${var.aws_account_id}"
  uploads_kept_for = 1
}

resource "aws_s3_bucket" "private_files" {
  bucket = local.private_bucket
}

resource "aws_s3_bucket_public_access_block" "private_files" {
  bucket                  = aws_s3_bucket.private_files.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_lifecycle_configuration" "private_files" {
  bucket = aws_s3_bucket.private_files.id

  rule {
    id     = "forget-the-uploads-never-kept"
    status = "Enabled"

    filter {
      prefix = "uploads/"
    }

    expiration {
      days = local.uploads_kept_for
    }

    abort_incomplete_multipart_upload {
      days_after_initiation = local.uploads_kept_for
    }
  }
}

# The browsers of the SPA send the files with a form posted from its origin; a download is a navigation, no CORS
resource "aws_s3_bucket_cors_configuration" "private_files" {
  count  = local.front_url != "" ? 1 : 0
  bucket = aws_s3_bucket.private_files.id

  cors_rule {
    allowed_methods = ["POST"]
    allowed_origins = [local.front_url]
    allowed_headers = ["*"]
    max_age_seconds = 3600
  }
}

resource "aws_s3_bucket_policy" "private_files" {
  bucket = aws_s3_bucket.private_files.id
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid       = "OnlyThroughTls"
      Effect    = "Deny"
      Principal = "*"
      Action    = "s3:*"
      Resource  = [aws_s3_bucket.private_files.arn, "${aws_s3_bucket.private_files.arn}/*"]
      Condition = { Bool = { "aws:SecureTransport" = "false" } }
    }]
  })

  depends_on = [aws_s3_bucket_public_access_block.private_files]
}
