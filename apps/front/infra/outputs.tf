# What another app needs is published in SSM Parameter Store under /prospecter/<instance>/front/<key>, never read
# from this state (ADR 0059)

output "url" {
  description = "The address of the SPA"
  value       = local.url
}
