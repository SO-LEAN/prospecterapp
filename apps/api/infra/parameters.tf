# The secret of Symfony, made once and kept in SSM (ADR 0059), read by the function at its start (bref-ssm:), never
# written in its variables: a plan shows them in a public log
resource "random_password" "app_secret" {
  length  = 48
  special = false
}

resource "aws_ssm_parameter" "app_secret" {
  name  = "${local.ssm_path}/app-secret"
  type  = "SecureString"
  value = random_password.app_secret.result
}

# What the other apps read of the api (ADR 0059): its address, the domain the edge gives it or its AWS address
resource "aws_ssm_parameter" "url" {
  name  = "${local.ssm_path}/url"
  type  = "String"
  value = "https://${local.api_host}"
}
