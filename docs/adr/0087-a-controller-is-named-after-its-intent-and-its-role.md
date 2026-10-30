# 0087. A controller is named after its intent and its role; a property after its type, a repository after its collection

- Date: 2026-10-30
- Status: Accepted
- Supersedes: for the names of the controllers, [0025](0025-one-business-intent-per-use-case-and-endpoint.md)
- Implemented by: the controllers of `apps/api/src/*/Controller` and `apps/storage/src/Controller`, the properties of the services of `apps/api/src`, `docs/clean-code.md`

## Context

A controller took the name of its intent (`StartProspecting`, ADR 0007, kept by 0025): nothing told it was a controller, and it took the name of the command of the core it dispatches, imported under an alias (`StartProspecting as StartProspectingCommand`, 27 controllers). The presenters already carried their role (`OrganizationPagePresenter`). The properties were named in the plural like the repositories, `$organizations` for an `OrganizationRepository` (Evans, the illusion of a collection in memory), but also for services that are no collection: `ImageResourceFactory $images`, `OrganizationReader $organizations`, `CommandBus $commands`. Michel could not tell at a glance what a class or a property was.

## Decision

- **A controller is named after its intent, suffixed by its role**: `StartProspectingController`, `ShowDashboardController`. The command or the query of the core keeps its name, imported without an alias. Every controller lives in the `Controller/` folder of its context, those composing contexts too (`Contacts`, `Exchanges`, `Dashboard`), apart from their readers and presenters. An adapter of a port keeps the name of what it reads, its folder naming the pattern (ADR 0086).
- **A repository is named after the collection it gives the illusion of**: `OrganizationRepository $organizations`, `$this->organizations->get($id)`.
- **Any other service is named after its type**: `ImageResourceFactory $imageResourceFactory`, `OrganizationReader $organizationReader`, `CommandBus $commandBus`; a type already in the plural keeps its name (`Links $links`), a query handler the name of its intent (`RefusalTranslatingHandler $consultOrganization`).
- **A variable holding several elements is named in the plural after what they are to the code reading them**, never after the repository they come from: `$callingOrganizations = $this->organizations->withPhoneNumber(...)`.

## Consequences

- The role of a class is read in its name; the intent stays its first word.
- `$this->organizations` is a repository wherever it is read.
- Longer names for the services.

## Alternatives considered

- `$organizationRepository` for the repositories, the habit of Symfony: Michel kept the plural of the collection.
- Keeping the controllers named after their intent alone (ADR 0007): the name told neither the role nor kept the one of the command free.

## Revisit when

A role other than controller, presenter, reader or factory appears in the API without its suffix.
