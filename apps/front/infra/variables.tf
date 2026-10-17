variable "instance" {
  description = "The installation of the platform (dev, prod, later test or recette): every resource is named prospecter-<instance>-front-<resource>"
  type        = string

  validation {
    condition     = can(regex("^[a-z][a-z0-9]{1,11}$", var.instance))
    error_message = "An instance is a short lowercase name, such as dev or prod."
  }
}

variable "aws_account_id" {
  description = "The AWS account of the instance: Terraform refuses to run against any other"
  type        = string

  validation {
    condition     = can(regex("^[0-9]{12}$", var.aws_account_id))
    error_message = "An AWS account id has 12 digits."
  }
}

variable "region" {
  description = "The AWS region of the resources"
  type        = string
  default     = "eu-west-3"
}

# tflint-ignore: terraform_unused_declarations
variable "permissions_boundary" {
  description = "The permissions boundary every role of the app will carry, when the account requires one"
  type        = string
  default     = null
}
