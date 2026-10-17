# The secrets of the storage, made once and kept in SSM (ADR 0059), read by the function at its start (bref-ssm:),
# never written in its variables: a plan shows them in a public log
resource "random_password" "app_secret" {
  length  = 48
  special = false
}

resource "aws_ssm_parameter" "app_secret" {
  name  = "${local.ssm_path}/app-secret"
  type  = "SecureString"
  value = random_password.app_secret.result
}

# The token of the services of the platform (InternalCaller): the api reads it here
resource "random_password" "token" {
  length  = 48
  special = false
}

resource "aws_ssm_parameter" "token" {
  name  = "${local.ssm_path}/token"
  type  = "SecureString"
  value = random_password.token.result
}

# What the other apps read of the storage (ADR 0059): the public address of the files and its internal entry
resource "aws_ssm_parameter" "public_url" {
  name  = "${local.ssm_path}/public-url"
  type  = "String"
  value = local.public_url
}

resource "aws_ssm_parameter" "internal_url" {
  name  = "${local.ssm_path}/internal-url"
  type  = "String"
  value = local.internal_url
}
