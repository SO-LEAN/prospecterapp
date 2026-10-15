# 0018. Readonly requests, responses and value objects; flat entity columns

- Date: 2026-10-15
- Status: Superseded by [0023](0023-tactical-ddd-in-the-core.md)
- Implemented by: 58eb5b5; `packages/core/src`

## Context

The core dated from 2018: untyped properties with `@var` docblocks, a getter per field, flat requests and responses of a dozen arguments, traits for shared behaviour, mutable value objects paired with `has*` flags because Doctrine cannot map a nullable embeddable.

## Decision

- Requests, responses, pages and value objects (`Address`, `GeoPoint`, `File`) are `final readonly` classes with public promoted properties; responses carry value objects.
- Entities keep private typed state with accessors; they store a value object's fields as plain columns (`addressCity`, `logoUrl`, same database columns as before) and build the value object in the getter. Embeddables and `has*` flags are gone.
- Traits became collaborators inside the hexagon (`FileStore`, `GeoLocalizer`, `ProspectedOrganizations`); ports are typed (`int` ids, `?GeoPoint` from the geocoder).
- Strict types in the core; dead code removed (`Customer`, `Call`, `Email`, `Event`, unused inverse collections).

## Consequences

- Value objects are valid by construction and cannot be changed by accident.
- The API reads properties instead of getters; renaming a property breaks the API at analysis time.
- Entities know which columns hold their value objects: a small persistence concern inside the domain.

## Alternatives considered

- Modernizing internally only (types, promotion, private readonly) while keeping the getters and public signatures, so that the API would not change: the full modernization, with the API following, was chosen.

## Revisit when

Doctrine supports nullable embeddables.
