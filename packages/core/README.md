# Prospecter core

**Prospecter core** (formerly cleanprospecter) holds the business rules of Prospecter, a prospecting application designed after Robert C. Martin's [clean architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html): pure PHP 8.5, no framework, reached from outside only through its use cases and its ports. Part of the prospecterapp monorepo: `apps/api/` delivers it over HTTP.

> A good architecture maximizes the number of decisions not made. (Uncle Bob)

## What it does

Three bounded contexts and a small shared kernel (ADR 0030):

- **Accounts**: the organizations using Prospecter and their members; consult my account, revise the identity of my organization, change its logo.
- **PersonalData**: the personal data of natural persons, read in clear or masked depending on who reads (ADR 0031); consult one person or many, rename, change the contact details or the picture; the contacts an account prospects: collect a contact for its organizations, change its job title, search them, forget one (ADR 0041, 0042).
- **Prospecting**: the organizations an account prospects, shared by its team; start prospecting, consult, search, revise the identity, change the contact details, relocate (geocoded), write observations, attach to or detach from a holding, change or remove the logo, add or remove a contact, stop prospecting.

Every use case is described by a French Gherkin feature in `features/` (`demenager.feature` for `Relocate`).

Next: the GDPR rights (anonymization, access export), then the prospecting events (calls, appointments, emails).

## How it is shaped

- `src/<Context>/Domain`: aggregates (`Organization`, `Account`, `Person`), value objects, repository interfaces and other ports, business exceptions in `Domain/Exception`.
- `src/<Context>/Application/<Intent>`: one use case per business intent, CQRS (ADR 0029): a command and its `CommandHandler` change the state and return nothing; a query and its `QueryHandler` present the answer to the output port its client gives (ADR 0085), read through a port of the application (ADR 0088).
- Requests and responses are plain data, never a class of the domain (ADR 0035); the client gives the identity of what it creates and the moment of each command (ADR 0036).
- A client calls every handler through `RefusalTranslatingHandler`: a refusal of the domain becomes a `UseCaseRefused` typed by `ErrorType` (not found, not allowed, not possible in its state, business rule violated).

## Using it

The delivery implements the ports (repositories, `Prospectors`, `Readers`, `KnownContacts`, `ProspectedOrganizations`, `Events`, `GeoLocation`, `Storage`), builds the services of the hexagon (`GeoLocalizer`, `FileStore`, `EmailUniqueness`, `OrphanContacts`) and injects them in the handlers (ADR 0044), then:

```php
$relocate = new RefusalTranslatingHandler(new RelocateHandler($organizations, $prospectors, $events, $geoLocalizer));
$relocate(new Relocate(
    prospectorId: $memberId,
    id: $organizationId,
    street: '2 rue des Clefs', postalCode: '68000', city: 'Colmar', country: 'FR',
    executedAt: $clock->now(),
));

$organization = new RefusalTranslatingHandler(new ConsultOrganizationHandler($organizations, $prospectors))(
    new ConsultOrganization($memberId, $organizationId),
);
```

The API registers the command handlers on a Symfony Messenger bus through the decorator (`docs/architecture/api.md`).

## Developing

From the repository root, in Docker: `make test-core` runs the scenarios (Behat) then the unit and architecture tests (PHPUnit); `make lint` and `make fix` cover the core. How to write a rule: `docs/architecture/tests.md`; conventions: `docs/architecture/core.md` and `docs/architecture/clean-code.md`.
