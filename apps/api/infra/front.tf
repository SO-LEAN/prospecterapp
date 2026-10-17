# What the api reads of the front (ADR 0059, 0066): the address of the SPA, the origin its CORS allow; behind an edge,
# the domain it gives the front (ADR 0067); in a new instance, none until the front is applied (ADR 0069)
data "aws_ssm_parameters_by_path" "front" {
  path            = "/prospecter/${var.instance}/front"
  with_decryption = false
}

locals {
  front_parameters = {
    for index, name in data.aws_ssm_parameters_by_path.front.names :
    trimprefix(name, "/prospecter/${var.instance}/front/") => data.aws_ssm_parameters_by_path.front.values[index]
  }
}

locals {
  front_url         = local.edge_domains.front != "" ? "https://${local.edge_domains.front}" : nonsensitive(lookup(local.front_parameters, "url", ""))
  cors_allow_origin = "^${replace(local.front_url, ".", "\\.")}$"
}
