# 0044. Dependencies are injected, never built; a PHPStan rule checks it

- Date: 2026-10-15
- Status: Accepted
- Implemented by: `tools/phpstan/DependenciesAreInjectedRule.php` (included by the `phpstan.dist.neon` of the core and the three apps), `apps/api/src/Shared/DependencyInjection/RefusalTranslatingHandlersPass.php`, `packages/core/tests/Acceptance/Application.php`

## Context

Classes built their own collaborators in their constructor: the handlers of the core their services (`new FileStore($storage)`, `new GeoLocalizer($geoLocation)`, `new EmailUniqueness($persons)`, `new OrphanContacts($organizations)`), the readers and controllers of the API the decorator of the query handlers (`new RefusalTranslatingHandler($consultOrganization)`), the storage its image library (`new Imagine()`). A class then hides what it depends on, and the decorator could be forgotten. The user wanted the dependency injection pushed all the way, as a rule.

## Decision

- A constructor receives its dependencies and builds nothing, but the exception it throws.
- A service of the monorepo (a class depending on an interface or on another service) is never built by the code: the Symfony container builds it in the apps, `tests/Acceptance/Application` (the composition root of the scenarios) in the core. The services of the hexagon are injected into the handlers.
- The API injects a query handler already wrapped in `RefusalTranslatingHandler`, by the name of its intent: `RefusalTranslatingHandler $consultOrganization` for `ConsultOrganizationHandler`, aliased by `RefusalTranslatingHandlersPass` as the command handlers are registered on the bus.
- `DependenciesAreInjectedRule`, a PHPStan rule shared by the four PHP codebases, fails `make lint` otherwise. Tests are not checked: they compose. The objects a library makes for one call (a `Passport`, a `SymfonyStyle`) are not services.

## Consequences

- Every dependency shows in the constructor; no class can skip the translation of the refusals.
- A query handler is typed `RefusalTranslatingHandler` where it is injected: its intent is in the name of the argument, a typo is an autowiring error at compile time.
- The services of the hexagon are registered in `apps/api/config/services.yaml`, and built once in the test application.

## Alternatives considered

- A `Queries::ask($query)` service in front of the query handlers: a query bus, refused by ADR 0029.

## Revisit when

- A query handler is needed with another decorator, or the core gets a second client with no container.
