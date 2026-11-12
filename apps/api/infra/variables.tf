variable "instance" {
  description = "The installation of the platform (dev, prod, later test or recette): every resource is named prospecter-<instance>-api-<resource>"
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
  description = "The version of the code of the api (code-version of make/aws.mk, passed by make as TF_VAR_code_version): a new one redeploys build/api.zip"
  type        = string
  default     = "none"
}

variable "permissions_boundary" {
  description = "The permissions boundary every role of the api carries, when the account requires one"
  type        = string
  default     = null
}

variable "integration_events_delivery" {
  description = "How the api delivers the events of its commands (ADR 0097, 0098): sync, the reactions within the command, until the reactions of the instance are put in service; outbox, written with the command and relayed to the bus, the worker running the reactions after it"
  type        = string
  default     = "sync"

  validation {
    condition     = contains(["sync", "outbox"], var.integration_events_delivery)
    error_message = "The delivery is sync or outbox."
  }
}
