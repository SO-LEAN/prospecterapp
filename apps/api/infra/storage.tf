# What the api reads of the storage (ADR 0059, 0065): its addresses, published in SSM by its Terraform, and its token,
# read by the function at its start. The storage of the instance is applied first.
locals {
  storage_ssm_path = "/prospecter/${var.instance}/storage"
}

data "aws_ssm_parameter" "storage_public_url" {
  name = "${local.storage_ssm_path}/public-url"
}

data "aws_ssm_parameter" "storage_internal_url" {
  name = "${local.storage_ssm_path}/internal-url"
}
