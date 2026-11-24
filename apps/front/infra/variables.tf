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

variable "code_version" {
  description = "The version of the code of the front (code-version of make/aws.mk, passed by make as TF_VAR_code_version): the release the SPA serves, put by apps/front/script/release"
  type        = string
  default     = "none"
}

variable "serves_the_documents" {
  description = "Whether the instance serves the documents generated from the code, under the SPA's /docs/ path (the event storming of the contexts): dev does, prod answers 404"
  type        = bool
  default     = false
}

# tflint-ignore: terraform_unused_declarations
variable "permissions_boundary" {
  description = "The permissions boundary every role of the app will carry, when the account requires one"
  type        = string
  default     = null
}
