# What the edge of the installation gives the front (ADR 0067), written in SSM under /prospecter/<instance>/edge/ by
# whoever runs the edge: the domain of each app and its certificate, and the secret the edge adds to the requests it
# passes on. Without them, the front keeps its AWS address and answers every request.
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
  edge_certificate  = nonsensitive(lookup(local.edge, "front-certificate", ""))
  has_origin_secret = nonsensitive(lookup(local.edge, "origin-secret", "") != "")
}

locals {
  # the functions compare a digest of the secret, never the secret itself
  origin_secret_digest = local.has_origin_secret ? nonsensitive(sha256(local.edge["origin-secret"])) : ""
}

# Where the edge sends the requests of the domain of the front
resource "aws_ssm_parameter" "edge_target" {
  name  = "/prospecter/${var.instance}/front/edge-target"
  type  = "String"
  value = aws_cloudfront_distribution.site.domain_name
}
