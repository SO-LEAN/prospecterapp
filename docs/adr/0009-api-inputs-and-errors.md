# 0009. Inputs have the shape of resources, unknown fields are rejected, errors are problem details

- Date: 2026-10-14
- Status: Accepted
- Implemented by: aa0ba05, 9ab9fc8; `config/packages/framework.yaml`, `EventListener/UseCaseExceptionListener`

## Context

The API read an address nested (`address.city`) and wrote it flat (`city`). A client sending back what it read had its unknown fields silently ignored, and since `PUT` replaces everything, the address was cleared without any error.

## Decision

- An input has the shape of its resource, minus the read-only fields (`OrganizationInput.address` like `OrganizationResource.address`).
- The serializer rejects unknown fields (`allow_extra_attributes: false`) and reports each of them as a violation (`collect_extra_attributes_errors: true`): 422.
- Every error is RFC 9457 `application/problem+json`: validation and business refusals 422 with `violations`, not found 404, forbidden 403, missing token 401. Violation paths are the form field names in the front.

## Consequences

- Sending a field the API does not know fails loudly instead of losing data.
- Sending a read resource back unchanged fails on its read-only fields (`id`, `logo`, `location`), on purpose.
- One error format for every client.

## Alternatives considered

- Accept and ignore unknown fields (Symfony's default): the bug above.
- `PATCH` with partial updates: the use cases replace whole objects today.

## Revisit when

Partial updates are needed (`PATCH`, JSON Merge Patch).
