# The rules in force, by subject

Read your subject's document before working on it. It states the rule in force, without its history, and names the records that justify it. Read the records ([`adr/`](adr/README.md), an immutable journal typed `architecture` or `domain`) only to know why a rule exists, or before changing it. A decision updates its subjects' documents in the same change (ADR 0090).

## Architecture

| Subject | Document |
|---|---|
| The core: the hexagon, use cases, CQRS, read models, rules, refusals, domain events, dependencies | [architecture/core.md](architecture/core.md) |
| The bounded contexts, what each one publishes to the others, their anticorruption layers | [architecture/contexts.md](architecture/contexts.md) |
| Messaging between the contexts, end to end: outbox, relay, bus, worker, failures, operations | [architecture/messaging.md](architecture/messaging.md) |
| Persistence: DynamoDB, documents, keys, retrievers, search, slices | [architecture/persistence.md](architecture/persistence.md) |
| The API: endpoints, inputs, resources, presenters, links, errors, access, wiring | [architecture/api.md](architecture/api.md) |
| Authentication: Cognito, tokens, sign-in | [architecture/auth.md](architecture/auth.md) |
| The MCP server: AIs reaching Prospecter as their member, and signing in on Cognito | [architecture/mcp.md](architecture/mcp.md) |
| Files: the storage app, the buckets, the sizes, the private files | [architecture/storage.md](architecture/storage.md) |
| The front: stack, folders, data, forms, navigation | [architecture/front.md](architecture/front.md), design rules in [architecture/design-system.md](architecture/design-system.md) |
| The commands: make targets and console commands | [architecture/commands.md](architecture/commands.md) |
| The local stack: Docker, MiniStack, the Lambdas, the hosts, the local certificate | [architecture/local-stack.md](architecture/local-stack.md) |
| How the local stack differs from the instances on AWS | [architecture/local-vs-aws.md](architecture/local-vs-aws.md) |
| The infrastructure on AWS: Terraform, instances, deployment | [architecture/infra.md](architecture/infra.md) |
| The monorepo and its builds | [architecture/monorepo.md](architecture/monorepo.md) |
| The workflow: branches, pull requests, CI, the backlog | [architecture/workflow.md](architecture/workflow.md) |
| The event storming generated from the code: what it shows, how it is generated, where it is served. Booking's wall: [the whole wall](event-storming/booking/wall.svg), its lanes [Booking page](event-storming/booking/booking-page.svg), [Public link](event-storming/booking/public-link.svg), [Appointment](event-storming/booking/appointment.svg) | [architecture/event-storming.md](architecture/event-storming.md) |
| Clean code | [architecture/clean-code.md](architecture/clean-code.md) |
| The tests: the core's scenarios and doubles, the API tests, the journeys | [architecture/tests.md](architecture/tests.md) |

## Domain

| Context | Document |
|---|---|
| Accounts: the organizations using Prospecter, their members, their roles | [domain/accounts.md](domain/accounts.md) |
| PersonalData: natural persons' personal data, and who reads them | [domain/personal-data.md](domain/personal-data.md) |
| Prospecting: the organizations an account prospects, their contacts, the exchanges | [domain/prospecting.md](domain/prospecting.md) |
| Reporting: each account's figures, kept from the other contexts' facts and rebuilt from their state | [domain/reporting.md](domain/reporting.md) |
| Connectors: the members' connections to outside services, Google Calendar first | [domain/connectors.md](domain/connectors.md) |
| Campaigns: the tags on organizations and contacts, to target them | [domain/campaigns.md](domain/campaigns.md) |
| Booking: each member's booking page, when and how they can be booked, its public link | [domain/booking.md](domain/booking.md) |

The business rules themselves are the core's scenarios (`packages/core/features`), one feature per intent.

## Operations

| Subject | Document |
|---|---|
| An account's Google client: what its administrator sets up in their organization's Google Cloud | [google-client.md](google-client.md) |

## History

- The decision records: [adr/README.md](adr/README.md), an index that `make adr-index` writes from their headers. To add one: [adr/TEMPLATE.md](adr/TEMPLATE.md).
- The 2026 upgrade, step by step, and its traps: [upgrade-2026.md](upgrade-2026.md).
- The documents ADR 0090 moved (the records before it name their old paths): `docs/auth.md`, `docs/clean-code.md`, `docs/design-system.md`, `docs/infra.md` and `docs/monorepo.md` are in `docs/architecture/` under the same name; `docs/core-tests.md` is `docs/architecture/tests.md`; `docs/front-architecture.md` is `docs/architecture/front.md`.
- The folders ADR 0200 renamed (the records before it name their old paths): each context's `Domain/Policy` is `Domain/Rule`, and `MemberPresentationPolicy` is `MemberPresentation`.
