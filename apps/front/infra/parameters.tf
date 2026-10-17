# What the SPA reads of the api (ADR 0059): the address of its HTTP API, given to the SPA by config.js. The api of
# the instance is applied first.
data "aws_ssm_parameter" "api_url" {
  name = "/prospecter/${var.instance}/api/url"
}

# What the others read of the front: its address (the origin the api allows) and the bucket the continuous
# deployment puts the releases in (tools/deploy/front)
resource "aws_ssm_parameter" "url" {
  name  = "/prospecter/${var.instance}/front/url"
  type  = "String"
  value = local.url
}

resource "aws_ssm_parameter" "bucket" {
  name  = "/prospecter/${var.instance}/front/bucket"
  type  = "String"
  value = aws_s3_bucket.site.id
}
