# What another app needs is published in SSM Parameter Store under /prospecter/<instance>/storage/<key>, never read
# from this state (ADR 0059)

output "public_url" {
  description = "The address the stored files are served at"
  value       = local.public_url
}
