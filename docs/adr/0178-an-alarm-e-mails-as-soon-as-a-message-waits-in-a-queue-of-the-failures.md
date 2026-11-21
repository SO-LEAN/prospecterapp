# 0178. An alarm e-mails as soon as a message waits in a queue of the failures, and again once it is empty

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/infra/alerting.tf`, `apps/api/infra/{variables,outputs}.tf`, `apps/api/infra/instance.tfvars.example`

## Context

What the asynchronous chain cannot do ends in one of two queues of an instance, kept 14 days: `relay-failures` (the relay could not publish outboxes, ADR 0098) and `integration-failures` (a reaction failed or was refused, ADR 0101). Nothing watched them: `app:consistency:check` (ADR 0105) was run by hand, so a reaction failing deterministically could wait there unseen while a member saw figures, tags or links that disagreed with the tables. The local stack cannot stand in for this: MiniStack 1.5.27 stores an alarm but evaluates it only when metrics are put, its SQS publishes none to its CloudWatch, and its e-mail subscriptions only log.

## Decision

- Each instance has one SNS topic of alerts, `prospecter-<instance>-api-alerts`, held by the api root (`alerting.tf`), the app that owns the queues. The addresses it e-mails are a value of the instance, `alert_emails` (a list, none by default), kept out of the repository with its other values (ADR 0059) and marked sensitive, the plan of a pull request being a public log; each subscription waits for the confirmation sent to its address.
- Each queue of the failures has an alarm, `<queue>-waiting`: `ApproximateNumberOfMessagesVisible` greater than 0 over 5 minutes (its maximum), missing data not breaching, telling the topic when it goes to alarm and when it goes back to OK. Its description gives the command of recovery: `app:outbox:republish --since=<moment>` for the relay, `app:integration:redrive` for the reactions, then `app:consistency:check` to measure what is left.
- The topic is published in SSM, `/prospecter/<instance>/api/alerts-topic`, for the alarms of the other apps (ADR 0069), and its name is an output of the root.
- The topic is not encrypted: the key SNS manages refuses CloudWatch, and a key of KMS of its own would cost for messages holding no data (an alarm, a queue, a count).
- Nothing of it is deployed locally.

## Consequences

- A failure is known within minutes, and its end too; `app:consistency:check` stays the measure of the gap, by hand.
- An instance whose values name no address has its alarms and tells nobody; the addresses are set in the values of the instance and in those the CI reads, before the apply.
- The role that applies an instance needs SNS on the topic and the CloudWatch alarms.
- An SQS queue idle for hours may stop sending its metrics; the first message wakes them, the alarm may then come some minutes later than five.

## Alternatives considered

- A single address, required: every plan of a pull request would fail until the values of each instance in the CI held it, and an instance may have nobody to tell yet. Weighed for #228.
- The other apps finding the topic by its name: values crossing the apps go through SSM (ADR 0059, 0069).

## Revisit when

The alerts need another channel than e-mail (a chat, a pager), or more than one audience.
