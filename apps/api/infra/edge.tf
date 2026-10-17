# What the edge of the installation gives the api (ADR 0067), written in SSM under /prospecter/<instance>/edge/ by
# whoever runs the edge: the domain of each app and its certificate, and the secret the edge adds to the requests it
# passes on. Without them, the api keeps its AWS address and answers every request.
data "aws_ssm_parameters_by_path" "edge" {
  path            = "/prospecter/${var.instance}/edge"
  with_decryption = true
}

locals {
  edge = {
    for index, name in data.aws_ssm_parameters_by_path.edge.names :
    trimprefix(name, "/prospecter/${var.instance}/edge/") => data.aws_ssm_parameters_by_path.edge.values[index]
  }
  # the domains and the certificates are no secrets, the secret is never written but in SSM
  edge_domains      = { for app in ["api", "front", "storage"] : app => nonsensitive(lookup(local.edge, "${app}-domain", "")) }
  edge_certificate  = nonsensitive(lookup(local.edge, "api-certificate", ""))
  has_origin_secret = nonsensitive(lookup(local.edge, "origin-secret", "") != "")
}

locals {
  origin_secret_parameter = "/prospecter/${var.instance}/edge/origin-secret"
}

# Where the edge sends the requests of the domain of the api, once it has one
resource "aws_ssm_parameter" "edge_target" {
  count = local.edge_domains.api != "" ? 1 : 0
  name  = "/prospecter/${var.instance}/api/edge-target"
  type  = "String"
  value = aws_apigatewayv2_domain_name.edge[0].domain_name_configuration[0].target_domain_name
}
