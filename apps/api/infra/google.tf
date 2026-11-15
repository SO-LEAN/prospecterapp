# The OAuth client of the instance at Google (ADR 0120, 0124), set by hand in SSM under /prospecter/<instance>/api/:
# its id in google-client-id, its secret in the SecureString google-client-secret. An instance without them keeps its
# api: the function reads them at its start only once both are there (bref-ssm: fails the start on a parameter missing),
# Google refusing until then the authorizations asked without a client.
data "aws_ssm_parameters_by_path" "api" {
  path            = local.ssm_path
  with_decryption = false
}

locals {
  google_client_id_parameter     = "${local.ssm_path}/google-client-id"
  google_client_secret_parameter = "${local.ssm_path}/google-client-secret"
  has_google_client = alltrue([
    for name in [local.google_client_id_parameter, local.google_client_secret_parameter] :
    contains(data.aws_ssm_parameters_by_path.api.names, name)
  ])
}
