# 0108. The instances deliver outbox, by the default of the code

- Date: 2026-11-12
- Type: architecture
- Status: Accepted
- Supersedes: for the instances keeping the delivery `sync` until their reactions are put in service, [0098](0098-each-commit-writes-an-outbox-relayed-to-an-eventbridge-bus.md) and [0101](0101-the-reactions-run-after-the-command-in-a-worker.md)
- Implemented by: `apps/api/infra/variables.tf` (the default of `integration_events_delivery`), `apps/api/Makefile` (`console`), `apps/api/infra/instance.tfvars.example`

## Context

The delivery of an instance was a value of the instance, `integration_events_delivery`, `sync` when absent. The values of an instance are kept out of the repository in two copies nothing synchronizes: the file of the instance, read by an apply run by hand, and the secret the workflows apply with. Switching an instance in one copy only would be undone by the next apply of the other, a merge on `dev` putting `sync` back without a word. This value is neither a secret nor a property of an installation, such as its account, its region or its permissions boundary: it decides a behaviour of the platform, and only while its reactions are put in service.

## Decision

- The default of `integration_events_delivery` is `outbox`: an instance takes the delivery `outbox` with the apply of the code that brings it, `dev` at the merge, `prod` at the release of `dev` to `main` (ADR 0058), the same apply creating the table `reporting` and the messaging when they do not exist yet.
- `make console INSTANCE=` reads the same default: `outbox` when the values of the instance name no delivery.
- An instance whose reactions must not run in the worker names `sync` in its values.
- `prod`, holding neither data nor users, takes the release without `app:search:rebuild` nor `app:reporting:rebuild`: its figures are right at zero.

## Consequences

- The delivery of an instance changes with the code, planned on the pull request, applied by the workflows, no value of the instance to change in two places.
- An instance holding data that takes the release has its figures at zero until `app:reporting:rebuild`, run by hand after the apply.
- The variable stays for an instance needing `sync`; once every instance runs `outbox`, it can go.

## Alternatives considered

- Switching `dev` by its values, `outbox` in its file and in the secret of the workflows, then an apply: weighed by Michel at the QA of ticket 66, refused: two copies to keep in step, for a value that is no property of the installation.
- On `prod`, keeping `sync` by its values during the release, removed after the rebuilds: refused by Michel, `prod` having no data to count.

## Revisit when

- Every instance runs `outbox`: the variable and the delivery `sync` of the instances can go.
- An instance holding data takes the release: its rebuilds right after the apply, or the delivery `sync` named until they run.
