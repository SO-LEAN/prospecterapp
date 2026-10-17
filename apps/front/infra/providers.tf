provider "aws" {
  region              = var.region
  allowed_account_ids = [var.aws_account_id]

  default_tags {
    tags = {
      project    = "prospecter"
      instance   = var.instance
      app        = "front"
      managed-by = "terraform"
    }
  }
}

# Read at every plan, even before the first resource, so that allowed_account_ids is enforced and a wrong profile
# fails before anything is planned
# tflint-ignore: terraform_unused_declarations
data "aws_caller_identity" "current" {
  lifecycle {
    postcondition {
      condition     = self.account_id == var.aws_account_id
      error_message = "The credentials are not those of the account of the instance."
    }
  }
}
