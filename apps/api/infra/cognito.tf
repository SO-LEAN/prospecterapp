# The user pool the members sign in with (ADR 0052), the configuration apps/api/src/SignIn/UserPool.php gives it in
# MiniStack: a member is a user named by its member id, signing in with the email of its alias; only the api creates
# them (without a message); two clients, the SPA signing in by SRP, and the AIs signing their member in on the sign-in
# page of the pool through the facade of the api (ADR 0157). The api finds them by their name. The tier Lite holds
# all of it (free up to 10 000 monthly users); the members are never deleted with it.
resource "aws_cognito_user_pool" "members" {
  name                = "${local.name}-members"
  alias_attributes    = ["email"]
  user_pool_tier      = "LITE"
  deletion_protection = "ACTIVE"

  password_policy {
    minimum_length    = 8
    require_lowercase = false
    require_uppercase = false
    require_numbers   = false
    require_symbols   = false
  }

  admin_create_user_config {
    allow_admin_create_user_only = true
  }
}

resource "aws_cognito_user_pool_client" "front" {
  name                          = "prospecter-front"
  user_pool_id                  = aws_cognito_user_pool.members.id
  explicit_auth_flows           = ["ALLOW_ADMIN_USER_PASSWORD_AUTH", "ALLOW_REFRESH_TOKEN_AUTH", "ALLOW_USER_SRP_AUTH"]
  prevent_user_existence_errors = "ENABLED"
}

# The AIs (ADR 0157): the code with PKCE on the sign-in page of the pool, Cognito sending the member back to the facade
# of the api, the only one knowing the secret (read by the api, never written anywhere)
resource "aws_cognito_user_pool_client" "mcp" {
  name                                 = "prospecter-mcp"
  user_pool_id                         = aws_cognito_user_pool.members.id
  generate_secret                      = true
  explicit_auth_flows                  = ["ALLOW_REFRESH_TOKEN_AUTH"]
  allowed_oauth_flows_user_pool_client = true
  allowed_oauth_flows                  = ["code"]
  allowed_oauth_scopes                 = ["openid"]
  callback_urls                        = ["https://${local.api_host}/oauth/callback"]
  supported_identity_providers         = ["COGNITO"]
  enable_token_revocation              = true
  prevent_user_existence_errors        = "ENABLED"
}

# The domain of the sign-in page, a prefix of Cognito: the classic hosted UI, the tier Lite having no editor of the
# managed login
resource "aws_cognito_user_pool_domain" "sign_in" {
  domain                = var.cognito_domain_prefix != "" ? var.cognito_domain_prefix : local.name
  user_pool_id          = aws_cognito_user_pool.members.id
  managed_login_version = 1
}

locals {
  cognito_domain_url = "https://${aws_cognito_user_pool_domain.sign_in.domain}.auth.${var.region}.amazoncognito.com"
}
