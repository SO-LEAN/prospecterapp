# 0131. The worker runs a bounded number of invocations at once, locally through a queue of its invocations

- Date: 2026-11-16
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/infra/messaging.tf`, `apps/api/infra/variables.tf` (`worker_concurrency`), `docker/lambda/deploy.sh` (`WORKER_CONCURRENCY`, `deploy_worker`), `apps/api/src/Shared/Messaging/QueuedIntegrationEventConsumer.php`

## Context

Each subscription is a rule of the bus, and each fact a rule matches is one invocation of the worker (ADR 0101): the invocations of a gesture grow with every reaction added to its facts. Stopping to prospect an organization of 36 exchanges asks the worker some 72 times at once once a second reaction listens to `ExchangeFactsChanged` (#94). On AWS the worker had no reserved concurrency: a burst could take every execution of the account, which the api shares (#101 found the worker throttled once on `dev`). Locally, MiniStack starts a container of 1 GB per execution at once, kept warm 10 minutes: from nothing, the journeys peaked at 5.7 GiB of MiniStack on `dev`, 8.5 GiB with #94, and MiniStack was killed twice at 12.5 GiB, the reading of the organizations after a stop of prospecting not answering within 5 seconds. Grouping the facts through a queue of SQS was refused (#140).

MiniStack 1.5.27 (its sources read for #140) refuses an invocation beyond the reserved concurrency of a function at once, without waiting (`TooManyRequestsException`), and invokes the target of a rule in a thread of its own, without retrying it: an invocation of a rule refused by the cap would be lost, only logged. A mapping of a queue of SQS, on the contrary, takes no more messages than its `MaximumConcurrency`, and a message whose invocation failed or was refused is received again after its visibility timeout.

## Decision

- **The worker has a reserved concurrency**, `worker_concurrency` of the instance (10 by default) on AWS, `WORKER_CONCURRENCY` (10) locally. On AWS an invocation beyond it is throttled and kept by Lambda, retried within the 6 hours of an event (ADR 0101), EventBridge retrying a day what it could not hand over: nothing is lost, and the executions of the account beyond the cap stay with the api.
- **Locally, the rules put the invocations of the worker in a queue**, `prospecter-dev-worker-invocations`, with the same input transformer; a mapping of the queue gives them to the worker one per invocation, as many at once as its concurrency (`MaximumConcurrency`). The handler of the local worker, `QueuedIntegrationEventConsumer`, applies each as the rule gives it on AWS; an invocation given directly (`app:integration:redrive`, the warming) goes to the worker as it comes.
- **A failure the worker throws locally is received again** after the visibility timeout of the queue (30 s), then, at its third receive, kept in the queue of the failures with the condition `RetriesExhausted`, as Lambda keeps what it gives up after its two retries: a failure coming from a rule is no longer only logged.
- The warming before the journeys starts as many workers as the concurrency; `make db` empties the queue of the invocations with those of the failures.

## Consequences

- A burst of reactions takes longer to drain: 72 reactions at 10 at once, each some 0.5 to 1 s warm, take some seconds more than when they all ran together. The SPA reads the queries the reactions change again while they run (`readAgainAfterTheReactions`), and the journeys rely on the retried assertions of Playwright.
- AWS keeps 10 executions of an account unreserved: a reservation of 10 needs an account accepting more than 20 executions at once; on an account limited to 10 (#101), the apply is refused.
- The local stack differs from AWS by one queue between the rules and the worker, and by the retries of a failure coming from a rule (the visibility timeout of the queue rather than the backoff of Lambda). The handler of AWS stays `IntegrationEventConsumer`.
- An invocation refused locally by the reserved concurrency while the warming or a redrive takes the worker is received again after 30 s, its receive counted: it may be kept in the failures after a first failure only.

## Alternatives considered

- Grouping the facts of a gesture through a queue of SQS on AWS too, the worker taking them by batches: refused by the ticket (#140), a cap being enough.
- Locally, the reserved concurrency alone, the rules invoking the worker directly as on AWS: an invocation beyond the cap is lost, MiniStack neither waiting nor retrying (its sources, 1.5.27).
- Locally, `LAMBDA_ACCOUNT_CONCURRENCY` bounding every function: the same loss, refused already by ADR 0116.

## Revisit when

MiniStack keeps or retries an invocation of a rule its reserved concurrency refuses, as Lambda does: the queue of the invocations can go. Or a burst grows enough for its delay to show on a screen.
