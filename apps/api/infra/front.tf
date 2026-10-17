# What the api reads of the front (ADR 0059, 0066): the address of the SPA, the origin its CORS allow. The front of
# the instance is applied first.
data "aws_ssm_parameter" "front_url" {
  name = "/prospecter/${var.instance}/front/url"
}

locals {
  cors_allow_origin = "^${replace(data.aws_ssm_parameter.front_url.insecure_value, ".", "\\.")}$"
}
