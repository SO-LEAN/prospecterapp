# 0005. Clean architecture: the core is the hexagon, the API adapts, the front presents

- Date: 2026-10-13
- Status: Accepted
- Implemented by: `packages/core`, `apps/api/src/Gateway`, root and core `CLAUDE.md`

## Context

The project started in 2018 as a clean architecture exercise: use cases with request, response and presenter, entities, gateway interfaces. The rewrite of the delivery (0006) could have let business rules leak into controllers or the front.

## Decision

- Business rules live in `packages/core`, in pure PHP: its `composer.json` requires only `php`.
- The core reaches the outside through ports (`src/Gateway`: entity gateways, storage, geolocation, notifier, transaction) that the API implements (`apps/api/src/Gateway`).
- A use case is `UseCase/<Name>/<Name>Impl` with its request, response and presenter; the API presents responses as JSON resources.
- The API only adapts (HTTP, Doctrine, storage, authentication); the front only presents.

## Consequences

- Rules can be tested without a framework or a database (0017).
- Delivery and infrastructure can be replaced without touching rules (the SPA rewrite did not change the core's behaviour).
- More types per feature: request, response, presenter, adapter.

## Alternatives considered

None were weighed: the project has followed this architecture since 2018, and the rewrite kept it.

## Revisit when

A rule cannot be expressed without infrastructure, or the boundaries cost more than they protect.
