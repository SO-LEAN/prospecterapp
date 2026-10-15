# 0007. Plain Symfony controllers, one per endpoint, resource-oriented URLs

- Date: 2026-10-14
- Status: Accepted
- Implemented by: aa0ba05; `apps/api/CLAUDE.md`

## Context

The API must expose use cases, not database resources: rules are in the core (0005). The use cases are CRUD-shaped today and will be renamed to business intents (0021).

## Decision

- No API Platform: plain Symfony controllers documented with Nelmio (0008).
- One invokable controller per endpoint, grouped by resource: `src/Controller/Api/<Resource>/<Action>.php` (`Organization/UploadLogo`).
- Input through `#[MapRequestPayload]` / `#[MapQueryString]` with readonly DTOs, output through presenters building readonly resources.
- URLs are resource-oriented (`/api/organizations/{id}`, `/api/me`) and never named after use cases.
- `PUT` replaces the whole resource (the update use cases overwrite every field); files are separate `POST .../logo` multipart sub-resources, since PHP does not parse multipart bodies on `PUT`.

## Consequences

- Each endpoint is small and readable on its own; renaming use cases only touches controllers, not URLs nor the front.
- More files than an action-per-method controller.
- Uploads read the current values to send them back with the file (full replacement).

## Alternatives considered

- API Platform: judged unnecessary by the user.
- One controller per resource with several action methods (the previous shape): the user asked for one controller per endpoint, grouped by resource.

## Revisit when

Use cases become commands (0020): controllers may then send commands instead of calling the facade.
