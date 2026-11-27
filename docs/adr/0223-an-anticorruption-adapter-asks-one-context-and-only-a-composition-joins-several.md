# 0223. An anticorruption adapter asks one context, and only a composition joins several

- Date: 2026-11-27
- Type: architecture
- Status: Accepted
- Implemented by: `packages/phpstan-rules/QueriesOfOneContextRule.php` and its test `packages/core/tests/PHPStan/QueriesOfOneContextRuleTest.php`; Booking's `PlannedExchange`, `LinkAvailability` and `HostIdentities` with the ports `Hosts` and `HostPresentations`; Campaigns' `AddresseeOrganization` and `Addressees` with the ports `AddresseeNames` and `AddresseeOrganizations`; Connectors' `Members` with the ports `AccountMembers` and `MemberFirstNames`, and the ports `ContactCorrespondents`, `OrganizationCorrespondents`, `ContactAddresses` and `OrganizationAddresses`; the API's `PersonsOfAViewComposition`

## Context

ADR 0217 says that an anticorruption adapter calls and translates, and never decides or enriches, and that a `*Composition` of the API joins answers by ids and holds no rule. Three adapters still decided: the time that an exchange without a duration keeps its host busy, whether a host may hold their bookers, and the organization that a campaign's message names. Several adapters also asked two contexts and joined their answers, for example the hosts' adapter, which asked Accounts and PersonalData. Nothing kept a class of the API from asking several contexts outside a composition.

## Decision

The maintainer took these decisions on the ticket (27/11). The last one about the rule replaces counting the queries of a class:

- An anticorruption adapter asks one context. A port that needs two contexts becomes one port per context. The core puts their answers together, and a rule that depends on them lives in the core.
- Only a `*Composition` of the API asks the queries of several contexts. A PHPStan rule refuses any other class of the code (not of the tests) that builds queries of more than one context. A query is a class that its `<Class>Handler` handles as a `QueryHandler`, or the question of a published service. Its context is the namespace that follows `Prospecter\Core\`. The rule counts contexts, not queries: a class that asks several queries of one context passes, such as the API's `TimelineReader` or Connectors' adapter of the held exchanges.
- A composition juxtaposes independent queries, of one context or of several. When the answer of one query sets or filters another query of the same context, that context offers the read model instead, named after a business question. The review checks this, not PHPStan (`docs/architecture/api.md`).
- `App\Identification\ContactRecorder` is the one named exemption. It records a contact and their organization in one gesture of an AI: it reads Prospecting and PersonalData, then runs their commands (ADR 0177).
- `PersonsOfAView`, which joins Accounts' and PersonalData's answers for the compositions, is a piece of composition and is named `PersonsOfAViewComposition`.

## Consequences

- A new adapter that asks a second context fails the analysis. Its author splits the port instead.
- The rules that the adapters held are now in the core and in its tests: Booking's `PlannedExchange` gives an exchange without a duration 15 minutes, and `LinkAvailability` decides that a host may hold their bookers while they have the right on personal data.
- Campaigns names in a message the organization that the contact was attached to most recently. For this, Prospecting keeps the moment of each attachment and publishes it in `NameOrganizationsOfContacts` with each organization's id. An attachment stored before has no moment and counts as the oldest. No data is migrated.
- The rule follows the classes that a class builds with `new`. A query built elsewhere and passed in is not counted.

## Alternatives considered

- Counting the queries of a class instead of its contexts. It would have split the adapters that ask two queries of one context, and `TimelineReader`, which chains none. The maintainer first wrote that the rule counts the queries of a class, then chose to count contexts.
- Counting the queries of each public method. The maintainer counted per class.
- Exempting the anticorruption adapters from the rule. It contradicted the rule that an adapter asks one context.

## Revisit when

A class needs the answers of two contexts and is neither a composition nor an orchestration of commands, or a query reaches a class other than through `new`.
