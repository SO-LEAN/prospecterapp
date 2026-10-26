# What the SPA reads of the api (ADR 0059): its address, given to the SPA by config.js; in a new instance, none until
# the api is applied, the front taking it at its next apply (ADR 0069)
data "aws_ssm_parameters_by_path" "api" {
  path            = "/prospecter/${var.instance}/api"
  with_decryption = false
}

locals {
  api_parameters = {
    for index, name in data.aws_ssm_parameters_by_path.api.names :
    trimprefix(name, "/prospecter/${var.instance}/api/") => data.aws_ssm_parameters_by_path.api.values[index]
  }
}

# What the others read of the front: its address (the origin the api allows) and the bucket the continuous
# deployment puts the releases in (apps/front/script/release)
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
