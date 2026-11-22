# 0193. PersonalData collects a contact by its Reader, not by a Gesture

- Date: 2026-11-22
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/PersonalData/Domain/Model/{Person,ContactCard,NewContact}.php`, `packages/phpstan-rules/DomainMethodsTakeFourParametersAtMostRule.php`, `docs/architecture/clean-code.md` (section 2), `docs/domain/personal-data.md`

## Context

ADR 0191 limits a public method of the domain to four parameters and says every aggregate's factories take a `Gesture`. `Person::collectAsContact()` took nine parameters, `collectToAttach()` five. Both take a `Reader`: the member, their account and whether they hold the right to manage personal data, which the factory checks itself. A `Gesture` beside it would give the account and the member twice. A PHPStan rule now keeps the threshold.

## Decision

- A contact is collected by its `Reader`, never by a `Gesture`: `collectAsContact(Reader, NewContact, at, origin)` and `collectToAttach(Reader, PersonId, ContactCard, at)`. The right to manage personal data stays checked in `Person`. This is the exception to ADR 0191: in PersonalData, the `Reader` stands for the `Gesture`.
- `ContactCard` groups what the card of a person says: their name, contact details, civility, job title and links. `NewContact` groups the person, their card and the organizations they work for.
- A profile has no `Reader` to check: it is collected by the `Gesture` of the member who brings it in, the founder who opens the account or the administrator who registers the newcomer: `collect(Gesture, PersonId, Purpose, ContactCard)`.
- `DomainMethodsTakeFourParametersAtMostRule` (PHPStan, in the core) refuses a public method of `*/Domain/Model` over four parameters, constructors exempt.

## Consequences

- The rule that only a member with the right collects a contact stays in the aggregate, whoever calls it.
- `collectAsContact()` takes the moment and the origin beside its `Reader`, as `Gesture` would carry them elsewhere.
- Three named constructors of value objects list their fields as a constructor does: `ExchangeFact::withStatus()`, `ContactSupplement::given()` and `OrganizationSupplement::given()`. They name their exception to the rule (`@phpstan-ignore prospecter.parameters`) and are listed in the clean code guide as known debt.

## Alternatives considered

- `collectAsContact(Gesture, PersonId, ContactCard, organizations)`, the handler checking the right through the `Reader` beforehand, as `Exchange::noted()` leaves the right of the prospector outside it: the maintainer kept the check in the aggregate.
- Both a `Reader` and a `Gesture`: the account and the member given twice, which could disagree.

## Revisit when

Another context's actor carries a right its aggregate checks, and wants the same exception.
