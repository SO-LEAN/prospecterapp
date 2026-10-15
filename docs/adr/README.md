# Architecture Decision Records

Why the project is shaped the way it is. One file per decision, numbered in the order the decisions were made.

The project is being refactored: these decisions are expected to change. Change them by writing a new record, not by editing an accepted one.

## Lifecycle

- `Proposed`: decided as a direction, not implemented yet.
- `Accepted`: in force, implemented (the record names the commits or files).
- `Superseded by NNNN`: replaced; only its status line is edited, with a link to the new record, which links back with `Supersedes NNNN`.
- `Deprecated`: no longer applies, nothing replaces it.

A decision is worth a record when it shapes the structure (where code lives, how parts talk to each other, which tool or protocol), is costly to reverse, or will surprise someone reading the code. Anything less belongs in a `CLAUDE.md` or `docs/`.

## Template

```markdown
# NNNN. Title stated as the decision

- Date: YYYY-MM-DD
- Status: Proposed | Accepted | Superseded by [NNNN](NNNN-....md)
- Implemented by: commits, files

## Context
The problem, the forces and constraints at the time.

## Decision
What was decided, in a few sentences.

## Consequences
What it makes easier, what it costs, the debt it leaves.

## Alternatives considered
Each option and why it was not chosen.

## Revisit when
The signal that should reopen this decision.
```

## Index

| # | Decision | Status | Date |
|---|---|---|---|
| [0001](0001-record-architecture-decisions.md) | Record architecture decisions | Accepted | 2026-10-15 |
| [0002](0002-monorepo-with-a-local-core-package.md) | One repository: front, API and core, the core as a local Composer package | Accepted | 2026-10-13 |
| [0003](0003-single-linear-main-branch.md) | A single linear `main`, short-lived branches, fast-forward only | Accepted | 2026-10-13 |
| [0004](0004-php-8-5-and-symfony-8-1.md) | PHP 8.5 and Symfony 8.1, native types, PHPStan level 5 | Accepted | 2026-10-13 |
| [0005](0005-clean-architecture-with-the-core-as-hexagon.md) | Clean architecture: the core is the hexagon, the API adapts, the front presents | Accepted | 2026-10-13 |
| [0006](0006-react-spa-and-json-api.md) | A React SPA and a JSON API replace the server-rendered application | Accepted | 2026-10-14 |
| [0007](0007-plain-symfony-controllers-one-per-endpoint.md) | Plain Symfony controllers, one per endpoint, resource-oriented URLs | Accepted | 2026-10-14 |
| [0008](0008-committed-openapi-contract.md) | The OpenAPI contract is generated, committed, checked and typed in the front | Accepted | 2026-10-14 |
| [0009](0009-api-inputs-and-errors.md) | Inputs have the shape of resources, unknown fields are rejected, errors are problem details | Accepted | 2026-10-14 |
| [0010](0010-oauth-2-authorization-code-with-pkce.md) | OAuth 2 authorization code with PKCE for the SPA | Accepted | 2026-10-14 |
| [0011](0011-authentication-outside-the-hexagon.md) | Authentication is infrastructure, outside the hexagon | Accepted | 2026-10-15 |
| [0012](0012-dedicated-authorization-server.md) | Delegate authentication to a dedicated authorization server | Proposed | 2026-10-15 |
| [0013](0013-front-stack.md) | Front stack: React, TanStack, typed client, shadcn/ui | Accepted | 2026-10-14 |
| [0014](0014-open-maps-and-state-geocoding.md) | Open maps (MapLibre, OpenFreeMap) and the French State geocoder | Accepted | 2026-10-14 |
| [0015](0015-local-stack-in-docker.md) | The whole stack runs locally in Docker, driven by make | Accepted | 2026-10-14 |
| [0016](0016-sqlite-without-migrations.md) | SQLite, schema generated from the mapping, no migrations | Accepted | 2026-10-13 |
| [0017](0017-gherkin-acceptance-tests-on-the-core.md) | Gherkin acceptance tests on the core, only ports doubled | Accepted | 2026-10-15 |
| [0018](0018-readonly-core-model.md) | Readonly requests, responses and value objects; flat entity columns | Accepted | 2026-10-15 |
| [0019](0019-organizations-reachable-only-by-their-prospector.md) | An organization is reachable only by the organization prospecting it | Accepted | 2026-10-15 |
| [0020](0020-command-bus-instead-of-the-facade.md) | Use cases as commands on a command bus, no query bus | Proposed | 2026-10-15 |
| [0021](0021-use-cases-named-after-business-intents.md) | Use cases named after business intents | Proposed | 2026-10-14 |
| [0022](0022-clean-code-conventions.md) | Clean code: names instead of comments, native types | Accepted | 2026-10-13 |
