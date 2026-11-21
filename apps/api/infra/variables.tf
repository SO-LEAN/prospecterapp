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
  description = "How the api delivers the events of its commands (ADR 0097, 0098, 0108): outbox, written with the command and relayed to the bus, the worker running the reactions after it; sync, the reactions within the command, for an instance whose reactions are not in service"
  type        = string
  default     = "outbox"

  validation {
    condition     = contains(["sync", "outbox"], var.integration_events_delivery)
    error_message = "The delivery is sync or outbox."
  }
}

variable "worker_concurrency" {
  description = "How many invocations of the worker run at once at most (ADR 0131, 0132): its reserved concurrency, taken from the executions of the account, a burst of reactions waiting for room instead of taking the executions of the api; null, the default, reserves nothing. An instance sets it once the quota of its account allows it (#101): AWS keeps a minimum of the executions of the account unreserved (100 by its documentation, all 10 of an account limited to 10)"
  type        = number
  default     = null

  validation {
    condition     = var.worker_concurrency == null || var.worker_concurrency >= 1
    error_message = "The worker runs at least one invocation at once, or reserves nothing (null)."
  }
}

variable "public_booking_rate_limit" {
  description = "How many requests a second the HTTP API lets through to the public booking pages, whatever their link and their address (ADR 0153): a cap of the route, the limit by link being the api's, that by address the edge's"
  type        = number
  default     = 10
}

variable "public_booking_burst_limit" {
  description = "How many requests at once the HTTP API lets through to the public booking pages beyond their rate (ADR 0153)"
  type        = number
  default     = 20
}

variable "public_booking_book_rate_limit" {
  description = "How many bookings a second the HTTP API lets through by the public booking links, whatever their link and their address (ADR 0164): a cap of the route, the limit by link being the api's, that by address the edge's"
  type        = number
  default     = 2
}

variable "public_booking_book_burst_limit" {
  description = "How many bookings at once the HTTP API lets through by the public booking links beyond their rate (ADR 0164)"
  type        = number
  default     = 5
}

variable "cognito_domain_prefix" {
  description = "The prefix of the domain of the sign-in page of the user pool (<prefix>.auth.<region>.amazoncognito.com, ADR 0157), unique in the region among every account of AWS: empty, the name of the api, prospecter-<instance>-api"
  type        = string
  default     = ""
}

variable "oauth_rate_limit" {
  description = "How many requests a second the HTTP API lets through to each route of the facade signing the AIs in and to the MCP server, whatever the address (ADR 0156, 0157): a cap of the route, the limit by address the edge's"
  type        = number
  default     = 10
}

variable "oauth_burst_limit" {
  description = "How many requests at once the HTTP API lets through to each route of the facade and to the MCP server beyond their rate (ADR 0156, 0157)"
  type        = number
  default     = 20
}

variable "alert_emails" {
  description = "The addresses the alarms of the instance e-mail (alerting.tf), each subscribed to its topic and confirmed from the e-mail AWS sends; none, the default, the alarms telling nobody. Sensitive: the plan of a pull request is a public log"
  type        = list(string)
  default     = []
  sensitive   = true

  validation {
    condition     = alltrue([for email in var.alert_emails : can(regex("^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$", email))])
    error_message = "An alert address is an e-mail address."
  }
}
