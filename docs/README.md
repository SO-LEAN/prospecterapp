# What is in force, by subject

Read the document of your subject before working on it: it says the rule in force, without its history, and names the records that justify it. The records ([`adr/`](adr/README.md), an immutable journal typed `architecture` or `domain`) are read only to know why a rule is, or before changing it; a decision updates the documents of its subjects in the same change (ADR 0090).

## Architecture

| Subject | Document |
|---|---|
| The core: the hexagon, use cases, CQRS, read models, policies, refusals, domain events, dependencies | [architecture/core.md](architecture/core.md) |
| The bounded contexts, what each publishes to the others, their anticorruption layers | [architecture/contexts.md](architecture/contexts.md) |
| Persistence: DynamoDB, documents, keys, retrievers, search, slices | [architecture/persistence.md](architecture/persistence.md) |
| The API: endpoints, inputs, resources, presenters, links, errors, access, wiring | [architecture/api.md](architecture/api.md) |
| Authentication: Cognito, tokens, sign-in | [architecture/auth.md](architecture/auth.md) |
| The files: the storage app, the buckets, the sizes, the private files | [architecture/storage.md](architecture/storage.md) |
| The front: stack, folders, data, forms, navigation | [architecture/front.md](architecture/front.md), design rules [architecture/design-system.md](architecture/design-system.md) |
| The local stack: Docker, MiniStack, the Lambdas, the hosts | [architecture/local-stack.md](architecture/local-stack.md) |
| The infrastructure on AWS: Terraform, instances, deployment | [architecture/infra.md](architecture/infra.md) |
| The monorepo and its builds | [architecture/monorepo.md](architecture/monorepo.md) |
| The workflow: branches, pull requests, CI, the backlog | [architecture/workflow.md](architecture/workflow.md) |
| Clean code | [architecture/clean-code.md](architecture/clean-code.md) |
| The tests: scenarios and doubles of the core, tests of the API, journeys | [architecture/tests.md](architecture/tests.md) |

## Domain

| Context | Document |
|---|---|
| Accounts: the organizations using Prospecter, their members, their roles | [domain/accounts.md](domain/accounts.md) |
| PersonalData: the personal data of natural persons and who reads them | [domain/personal-data.md](domain/personal-data.md) |
| Prospecting: the organizations an account prospects, their contacts, the exchanges | [domain/prospecting.md](domain/prospecting.md) |

The business rules themselves are the scenarios of the core (`packages/core/features`), each feature an intent.

## History

- The decision records: [adr/README.md](adr/README.md).
- The upgrade of 2026, step by step, and its traps: [upgrade-2026.md](upgrade-2026.md).
- The documents moved by ADR 0090 (the records before it name their former paths): `docs/auth.md`, `docs/clean-code.md`, `docs/design-system.md`, `docs/infra.md` and `docs/monorepo.md` are in `docs/architecture/` under the same name, `docs/core-tests.md` is `docs/architecture/tests.md`, `docs/front-architecture.md` is `docs/architecture/front.md`.
