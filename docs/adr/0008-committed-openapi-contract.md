# 0008. The OpenAPI contract is generated, committed, checked and typed in the front

- Date: 2026-10-14
- Status: Accepted
- Implemented by: aa0ba05, f0fbd58; `make api-doc`

## Context

The SPA and the API are two applications (0006). A renamed field must not break the front silently in production.

## Decision

- NelmioApiDocBundle generates the OpenAPI document from routes, `#[OA\...]` attributes and DTOs; Swagger UI on `/api/doc`.
- `make api-doc` writes it to `apps/api/openapi.json` (committed) and generates the front types (`src/shared/api/schema.d.ts`, openapi-typescript); the front calls the API through `openapi-fetch` and `openapi-react-query`, typed by them.
- `tests/Api/OpenApiContractTest` fails when the committed contract differs from the code.

## Consequences

- An API change shows in the diff of the contract and fails the front type check where it matters.
- The front builds without running the API.
- Forgetting `make api-doc` fails `make test`; the front types are trusted to be regenerated with it.

## Alternatives considered

- Contract-first (hand-written OpenAPI, code generated or checked against it): heavier while there is a single client in the same repository.
- Front types written by hand: drift.
- A shared `contracts/` folder: only worth it with several clients.

## Revisit when

A second client consumes the API (move the contract to a shared place), or the contract is designed before the code.
