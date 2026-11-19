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

# The key the facade signing the AIs in seals what it must find again with (ADR 0157), 32 bytes in base64, made once
# and kept in SSM as the secret of Symfony
resource "random_bytes" "oauth_seal_key" {
  length = 32
}

resource "aws_ssm_parameter" "oauth_seal_key" {
  name  = "${local.ssm_path}/oauth-seal-key"
  type  = "SecureString"
  value = random_bytes.oauth_seal_key.base64
}

# What the other apps read of the api (ADR 0059): its address, the domain the edge gives it or its AWS address
resource "aws_ssm_parameter" "url" {
  name  = "${local.ssm_path}/url"
  type  = "String"
  value = "https://${local.api_host}"
}
