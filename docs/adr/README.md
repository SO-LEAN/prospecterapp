# Architecture Decision Records

Why the project is shaped the way it is: one file per decision in `docs/adr/`, in the order they were made. The project is being refactored and these decisions will change: a change is a new record superseding the old one ([TEMPLATE.md](TEMPLATE.md)). Read a record before working on its subject.

| # | Decision | Status | Date |
|---|---|---|---|
| [0001](0001-record-architecture-decisions.md) | Record architecture decisions | Accepted | 2026-10-15 |
| [0002](0002-monorepo-with-a-local-core-package.md) | One repository: front, API and core, the core as a local Composer package | Accepted | 2026-10-13 |
| [0003](0003-single-linear-main-branch.md) | A single linear `main`, short-lived branches, fast-forward only | Accepted | 2026-10-13 |
| [0004](0004-php-8-5-and-symfony-8-1.md) | PHP 8.5 and Symfony 8.1, native types, PHPStan level 5 | Accepted | 2026-10-13 |
| [0005](0005-clean-architecture-with-the-core-as-hexagon.md) | Clean architecture: the core is the hexagon, the API adapts, the front presents | Accepted | 2026-10-13 |
| [0006](0006-react-spa-and-json-api.md) | A React SPA and a JSON API replace the server-rendered application | Accepted | 2026-10-14 |
| [0007](0007-plain-symfony-controllers-one-per-endpoint.md) | Plain Symfony controllers, one per endpoint, resource-oriented URLs | Superseded by 0025 | 2026-10-14 |
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
| [0018](0018-readonly-core-model.md) | Readonly requests, responses and value objects; flat entity columns | Superseded by 0023 | 2026-10-15 |
| [0019](0019-organizations-reachable-only-by-their-prospector.md) | An organization is reachable only by the organization prospecting it | Superseded by 0026 | 2026-10-15 |
| [0020](0020-command-bus-instead-of-the-facade.md) | Use cases as commands on a command bus, no query bus | Proposed | 2026-10-15 |
| [0021](0021-use-cases-named-after-business-intents.md) | Use cases named after business intents | Superseded by 0025 | 2026-10-14 |
| [0022](0022-clean-code-conventions.md) | Clean code as taught by Robert C. Martin, rules in `docs/clean-code.md` | Accepted | 2026-10-13 |
| [0023](0023-tactical-ddd-in-the-core.md) | Tactical DDD in the core: aggregates, value objects, repositories | Accepted, persistence superseded by 0028 | 2026-10-15 |
| [0024](0024-uuid-v7-identities-given-by-the-repository.md) | Identities are UUID v7 given by the repository | Accepted | 2026-10-15 |
| [0025](0025-one-business-intent-per-use-case-and-endpoint.md) | One business intent per use case and per endpoint; edition by section | Accepted | 2026-10-15 |
| [0026](0026-roles-in-the-api-ownership-and-state-in-the-aggregate.md) | Roles are checked by the API, ownership and state are decided by the aggregate | Accepted | 2026-10-15 |
| [0027](0027-hypermedia-links-from-the-possible-intents.md) | Resources carry hypermedia links built from the possible intents of the core | Accepted | 2026-10-15 |
| [0028](0028-aggregates-keep-their-value-objects.md) | Aggregates keep their value objects; the Doctrine mapping adapts to them | Accepted | 2026-10-15 |
