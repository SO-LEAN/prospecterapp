# 0179. An alarm e-mails when a function fails or is throttled, a scheduled task fails, or an entry answers 5xx

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/infra/alerting.tf`, `apps/storage/infra/alerting.tf`, their `variables.tf` and `instance.tfvars.example`

## Context

ADR 0178 gave each instance a topic of alerts, told when a message waits in a queue of the failures. Nothing else was watched: a function crashing, its invocations throttled once the executions of the account are exhausted (the worker can take them all while `worker_concurrency` reserves nothing, ADR 0132), the HTTP API answering 5xx, or a scheduled task failing every night went unseen. A task of the daily run failing does not fail its invocation: it is logged, `A scheduled task failed`, and the others run (ADR 0161). The storage has no HTTP API: its entry is a signed function URL behind CloudFront (ADR 0068).

## Decision

- Each function, `api`, `scheduled`, `relay`, `worker` and `storage`, has two alarms, `<function>-errors` (`AWS/Lambda` `Errors`) and `<function>-throttled` (`Throttles`): their sum over 5 minutes above a threshold, missing data not breaching, telling the topic at the alarm and back to OK. The thresholds are values of the instance, `function_errors_threshold` and `function_throttles_threshold`, 0 by default; `scheduled` alerts at its first error whatever the value, its run being daily.
- A scheduled task failing is counted by a metric filter on the log group of `scheduled`, `{ $.message = "A scheduled task failed" }` (the logs are JSON on an instance), the metric `ScheduledTaskFailures` of the namespace `Prospecter/<instance>/api`, and alarmed at its first failure, `<scheduled>-task-failed`. The function is unchanged.
- The answers 5xx are alarmed over 5 minutes above `http_5xx_threshold` (5 by default): `AWS/ApiGateway` `5xx` of the HTTP API of the api (`ApiId`), `AWS/Lambda` `Url5xxCount` of the function URL of the storage.
- Each description names the log group to read; those of the relay and the worker give the commands of recovery of ADR 0178, that of `scheduled` `app:scheduled-tasks:run`.
- The storage reads the topic by path in SSM, `/prospecter/<instance>/api/alerts-topic` (ADR 0069): until the api published it, its alarms tell nobody.
- No alarm on `Duration`: an invocation reaching its timeout is already counted in `Errors`, and a slower one below it is no failure.
- Nothing of it is deployed locally (ADR 0178).

## Consequences

- A crash, a throttle, a failed task or a burst of 5xx is known within minutes, and its end too.
- The role that applies needs `logs:PutMetricFilter` on the log group of `scheduled`, and, for the storage, to read the parameters of the api by path.
- A new instance converges in two applies for the alarms of the storage, like the other values between apps.
- A 5xx of the storage is not seen by CloudFront's metrics (in `us-east-1`, mixing the misses of S3): only what reaches the function counts.
- The log line `A scheduled task failed` is now a contract with `apps/api/infra/alerting.tf`: renaming it silences the alarm.

## Alternatives considered

- The storage receiving the topic as a value of its instance, or making a topic of its own: offered by #229; values crossing the apps go through SSM (ADR 0059, 0069, 0178), and a second topic doubles the subscriptions to confirm.
- An alarm on `Duration` near the timeout of the relay and the worker: left open by #229, not taken for the reason above.

## Revisit when

A function needs a threshold of its own, or the 5xx of the storage must be seen at the edge.
