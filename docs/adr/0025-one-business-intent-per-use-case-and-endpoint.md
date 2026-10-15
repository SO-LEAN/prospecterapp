# 0025. One business intent per use case and per endpoint; edition by section

- Date: 2026-10-15
- Status: Accepted
- Supersedes: [0007](0007-plain-symfony-controllers-one-per-endpoint.md), [0021](0021-use-cases-named-after-business-intents.md)
- Implemented by: `packages/core/src/Application`, `packages/core/features`, `apps/api/src/Controller/Api`, `apps/front/src/features`

## Context

The use cases were CRUD-shaped (`createOrganization`, `updateOrganization`, `updateMyAccountInformation`) and `PUT` replaced a whole organization or account: a form sent everything, the use case overwrote every field. The user avoids the CRUD vocabulary: a use case expresses what the user means to do, changes only the data of that intent, and is reused only by another client or a data recovery, never by another use case.

## Decision

- Use cases are named after intents: `StartProspecting`, `ReviseLegalIdentity`, `ChangeContactDetails`, `Relocate`, `WriteObservations`, `AttachToHolding`, `DetachFromHolding`, `ChangeLogo`, `RemoveLogo`, `ConsultOrganization`, `SearchProspectedOrganizations`, and for the account `ChangeMyName`, `ChangeMyUserName`, `ChangeMyContactDetails`, `ChangeMyPicture`, `ReviseMyOrganizationIdentity`, `ChangeMyOrganizationLogo`, `ConsultMyAccount`. One Gherkin feature per intent.
- One intent per endpoint, one invokable controller per endpoint grouped by resource (kept from 0007), plain Symfony controllers documented with Nelmio, resource-oriented URLs.
- HTTP methods: `POST` creates a resource or adds an entity to an aggregate (`POST /api/organizations`, `POST .../logo`), `PATCH` changes one part of an existing resource, exposed as a sub-resource (`PATCH /api/organizations/{id}/contact-details`), `DELETE` removes one (`DELETE .../holding`). No `PUT`.
- Every change returns the fresh resource. The password stays outside the core: `PATCH /api/me/password` is served by `Auth`.
- The front edits by section: a card per intent, its own small form, its own mutation; only the creation keeps a full form.

## Consequences

- A request can only change what its intent covers: two users editing different sections of the same organization no longer overwrite each other.
- More endpoints, controllers, inputs and features; the front composes a page from sections.
- A violation of the domain names its field relative to the intent (`email`): in the full creation form, which nests fields (`contactDetails.email`), it is shown above the form instead of on the field.

## Alternatives considered

- Sub-resources replaced with `PUT`: the user keeps `POST` for creation and `PATCH` for a change of a part.
- Keeping the full edition form and sending one command per changed section: the user chose edition by section, one form per intent.

## Revisit when

Use cases become commands on a command bus (0020): controllers then send a command per intent.
