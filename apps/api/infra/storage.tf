# What the api reads of the storage (ADR 0059, 0065): its addresses, published in SSM by its Terraform, and its token,
# read by the function at its start; behind an edge, the domain it gives the storage (ADR 0067); in a new instance,
# none until the storage is applied (ADR 0069).
locals {
  storage_ssm_path   = "/prospecter/${var.instance}/storage"
  storage_public_url = local.edge_domains.storage != "" ? "https://${local.edge_domains.storage}" : nonsensitive(lookup(local.storage_parameters, "public-url", ""))
}

data "aws_ssm_parameters_by_path" "storage" {
  path            = "/prospecter/${var.instance}/storage"
  with_decryption = false
}

locals {
  storage_parameters = {
    for index, name in data.aws_ssm_parameters_by_path.storage.names :
    trimprefix(name, "/prospecter/${var.instance}/storage/") => data.aws_ssm_parameters_by_path.storage.values[index]
  }
}
