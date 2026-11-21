# What another app needs is published in SSM Parameter Store under /prospecter/<instance>/api/<key>, never read
# from this state (ADR 0059)

output "tables" {
  description = "The tables of the bounded contexts"
  value       = { for context, table in aws_dynamodb_table.context : context => table.name }
}

output "alerts_topic" {
  description = "The topic the alarms of the instance tell, its ARN published in SSM under alerts-topic for the other apps"
  value       = aws_sns_topic.alerts.name
}
