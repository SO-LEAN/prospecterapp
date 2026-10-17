# The user pool the members sign in with (ADR 0052), the configuration apps/api/src/SignIn/UserPool.php gives it in
# MiniStack: a member is a user named by its member id, signing in with the email of its alias; only the api creates
# them (without a message); one client, the SPA, signing in by SRP. The api finds it by its name. The tier Lite holds
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
