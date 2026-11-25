# Domain-Driven Design

This document states the rules in force. The decision records (`docs/adr`) explain why. Read them only for that, or before changing a rule (ADR 0090).

Prospecter follows Domain-Driven Design. Read the code in its terms: each folder of the core is a pattern of Evans and Vernon. This page names each pattern that Prospecter keeps and says where it lives. The rules are in the documents it links to, and this page does not repeat them.

## Strategic patterns

| Pattern | Where it lives |
|---|---|
| Bounded context | A folder of `packages/core/src` (`Accounts`, `PersonalData`, `Prospecting`, `Reporting`, `Connectors`, `Campaigns`, `Booking`), and the same folder in `apps/api/src` for its delivery. [contexts.md](contexts.md) lists them, [ADR 0030](../adr/0030-bounded-contexts-accounts-prospecting.md). |
| Ubiquitous language | Each context's document in [`docs/domain/`](../domain/), its French scenarios in `packages/core/features` ([ADR 0017](../adr/0017-gherkin-acceptance-tests-on-the-core.md)), and the code's names in English. |
| Context map | The generated overview of the contexts, `apps/front/public/docs/event-storming/index.html` (see "Domain discovery" below), and the tables of [contexts.md](contexts.md), "What each publishes". |
| Published language | Each context's `Contract/`: the services it answers and the integration events it publishes, as plain data ([ADR 0045](../adr/0045-published-language-between-contexts.md), [0046](../adr/0046-integration-events-between-contexts.md), [0047](../adr/0047-a-contract-per-context.md), [0089](../adr/0089-a-contract-declares-the-service-it-offers.md)). |
| Anticorruption layer | A port in the context's `Domain/Port`, implemented in `apps/api/src/<Context>/AntiCorruption` from the other context's Contract only ([contexts.md](contexts.md), "What a context sees of another"). |
| Shared kernel | `packages/core/src/SharedKernel`, kept small: the account's and the member's identities, the base of the events, the generic refusals, and the value objects several contexts share ([core.md](core.md), [ADR 0197](../adr/0197-each-context-has-its-own-identities.md), [0201](../adr/0201-the-shared-kernel-is-grouped-by-concept-in-its-domain-and-by-role-in-its-application.md)). |

## Tactical patterns

Each one lives in a context's `Domain`. [core.md](core.md) gives their rules ([ADR 0023](../adr/0023-tactical-ddd-in-the-core.md)).

| Pattern | Where it lives | Its rules |
|---|---|---|
| Aggregate | `Domain/Model`: the classes that record events (`Organization`, `Exchange`, `Account`, `Person`, `BookingPage`) | [core.md](core.md), "Rules": named constructors, intention methods, no getter, one aggregate per use case |
| Entity | `Domain/Model`, inside its aggregate and reached through it (`Attachment` in `Exchange`) | [core.md](core.md), "Rules" |
| Value object | `Domain/Model`, `final readonly`, held as it is by its aggregate ([ADR 0028](../adr/0028-aggregates-keep-their-value-objects.md)) | [core.md](core.md), "Rules" |
| Domain event | `Domain/Event`, one per intention, in the past tense ([ADR 0042](../adr/0042-contacts-never-alone-domain-events.md), [0043](../adr/0043-a-domain-event-per-intention-and-an-activity-journal.md)) | [core.md](core.md), "Rules", and [messaging.md](messaging.md) |
| Repository | The interface in `Domain/Repository`, its DynamoDB adapter in `apps/api/src/<Context>/Persistence` | [core.md](core.md), [persistence.md](persistence.md) |
| Domain service | `Domain/Service`, a rule on a set of aggregates ([ADR 0039](../adr/0039-set-rules-in-domain-services-indexes-as-safety-net.md)) | [core.md](core.md), "Rules" |
| Rule | `Domain/Rule`, a pure, stateless rule (`OrganizationPossibilities`, `Masking`, [ADR 0200](../adr/0200-a-policy-is-a-reaction-as-in-event-storming-a-pure-domain-rule-is-a-rule.md)) | [core.md](core.md), "Layout" |
| Module | `Application/<Module>`, a subject of the context ([ADR 0206](../adr/0206-a-context-groups-its-use-cases-in-modules.md)) | [core.md](core.md), "Layout", and "Modules" below |

The application around the domain follows CQRS: one use case per intent, a command or a query ([ADR 0029](../adr/0029-cqrs-with-a-synchronous-messenger-command-bus.md)). A reaction is a policy, in the event storming's sense ([ADR 0203](../adr/0203-a-reaction-is-a-policy-that-turns-an-event-into-a-command-and-a-use-case-does-the-work.md)).

## Modules

Evans groups a context's code by business concept, not by technical kind. Prospecter applies this in one layer only.

- The application is grouped in business modules: `Application/<Module>/<Intent>`, a module per subject of the context (ADR 0206).
- The domain is grouped by kind of building block: `Model/`, `Repository/`, `Service/`, `Rule/`, `Port/`, `Event/`, `Exception/` ([ADR 0086](../adr/0086-the-domain-of-a-context-is-grouped-by-kind-of-building-block.md)). A value object often serves several aggregates, so a folder per aggregate would scatter it. For contexts of this size, this layout stays easier to read.
- We would group the domain by concept only when a context became hard to read.

## The core as a package, a legacy

The core is a package of its own, `packages/core`, with its own `composer.json`. The API is its only user ([monorepo.md](monorepo.md)).

- **Why.** It is a choice of the proof of concept. The business rules cannot reach the framework or the SDKs: these classes are absent from the core's vendor. The package also keeps the application reusable, in another framework or in a command-line application alone.
- **The hexagon and the package do different things.** The hexagon, the ports and their adapters, makes the use cases independent of the framework. The package lets a second application, in another project, depend on the core without the API. Without the package, a command-line application would live in the API's project, or the core would be extracted the day a second application asked for it.
- **A legacy.** That second application never came. Most modular monoliths keep each context in the application's `src`, with its layers inside. Next to them, this layout can look heavy.
- **What it costs.** Each context spans two trees (`packages/core/src/<Context>` and `apps/api/src/<Context>`), Composer needs its `path` repository, and the core has its own lint and test configurations.
- **We keep it.** Moving the core into the API is out of scope.

## Domain discovery

The event storming is generated from the code ([event-storming.md](event-storming.md), [ADR 0213](../adr/0213-the-event-storming-is-one-wall-per-context-with-a-lane-per-module.md), [0214](../adr/0214-every-context-has-its-wall-generated-from-the-whole-code-and-checked-in-the-ci.md)).

- Each context's wall is the view by concept that the domain's folders do not give: [Accounts](../event-storming/accounts/wall.svg), [Booking](../event-storming/booking/wall.svg), [Campaigns](../event-storming/campaigns/wall.svg), [Connectors](../event-storming/connectors/wall.svg), [Personal data](../event-storming/personal-data/wall.svg), [Prospecting](../event-storming/prospecting/wall.svg), [Reporting](../event-storming/reporting/wall.svg). Its lanes are the context's modules.
- The overview of the contexts is the context map. It draws an arrow from one context to another when the other's policies hear its events or when its ports ask the other's services. The local stack serves it at https://prospecter.localhost/docs/event-storming/, from `apps/front/public/docs/event-storming/index.html`.

## What Prospecter leaves out, or does otherwise

| Pattern | What Prospecter does instead |
|---|---|
| Event sourcing | An aggregate is saved as its state, one document in its context's table ([ADR 0048](../adr/0048-dynamodb-one-table-per-context-on-ministack.md)). Its events inform the reactions and the other contexts; they never rebuild it. |
| Factory classes | The aggregate's named constructors build it ([ADR 0023](../adr/0023-tactical-ddd-in-the-core.md)). |
| Sagas and process managers | A policy turns an event into a command, and an ordinary use case does the work. One aggregate changes per use case ([ADR 0042](../adr/0042-contacts-never-alone-domain-events.md), [0203](../adr/0203-a-reaction-is-a-policy-that-turns-an-event-into-a-command-and-a-use-case-does-the-work.md)). |
| Getters for reading | A query reads its read model through a port, from the aggregate's table, without restoring the aggregate ([ADR 0088](../adr/0088-a-query-reads-its-read-model-through-a-port-a-policy-says-what-is-possible.md)). Only Reporting keeps projections ([ADR 0103](../adr/0103-the-reporting-context-and-its-rebuildable-projections.md)). |
| A model shared between contexts | Each context has its own identities and words. Only integration events and published services cross the contexts ([ADR 0045](../adr/0045-published-language-between-contexts.md), [0197](../adr/0197-each-context-has-its-own-identities.md)). |
| "Policy" for a domain rule | A pure rule of the domain is a `Rule`. "Policy" keeps the event storming's meaning, a reaction ([ADR 0200](../adr/0200-a-policy-is-a-reaction-as-in-event-storming-a-pure-domain-rule-is-a-rule.md)). |
| Domain modules by aggregate | The domain is grouped by kind of building block (ADR 0086, "Modules" above). |
