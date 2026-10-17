# What the api reads of the front (ADR 0059, 0066): the address of the SPA, the origin its CORS allow; behind an edge,
# the domain it gives the front (ADR 0067). The front of the instance is applied first.
data "aws_ssm_parameter" "front_url" {
  name = "/prospecter/${var.instance}/front/url"
}

locals {
  front_url         = local.edge_domains.front != "" ? "https://${local.edge_domains.front}" : data.aws_ssm_parameter.front_url.insecure_value
  cors_allow_origin = "^${replace(local.front_url, ".", "\\.")}$"
}
