# 0046. Contexts exchange published integration events; domain events stay in their context

- Date: 2026-10-16
- Status: Accepted, location superseded by [0047](0047-a-contract-per-context.md)
- Supersedes: the events shared in `SharedKernel\Domain\Event` of [0042](0042-contacts-never-alone-domain-events.md) and [0043](0043-a-domain-event-per-intention-and-an-activity-journal.md)
- Implemented by: `packages/core/src/*/Application/Published/Event`, `PersonalData\Application\{PublishContactCollected,PublishContactForgotten}`, `Prospecting\Application\PublishContactsLeftWithoutOrganization`, `packages/core/tests/Architecture/BoundedContextsTest.php`

## Context

Since 0045 the contexts ask each other only the queries they publish. The three events crossing contexts (`ContactCollected`, `ContactForgotten`, `ContactsLeftWithoutOrganization`) stayed in the shared kernel: domain events, with value objects, recorded by one context and handled by another. That is the shared language 0045 refused for the queries: the model of the emitter was the contract of the receiver, and the shared kernel grew with every event crossing.

## Decision

- A domain event stays in the context that records it (`PersonalData\Domain\Event\ContactCollected`...): internal, rich, journaled (0043).
- What another context must know is an integration event, published in `<Context>\Application\Published\Event`: plain data (strings, lists of strings, the moment), built from the domain event (`ContactCollected::of()`).
- A handler of the emitting context translates its domain event into the integration event and publishes it on the port `Events` (`PublishContactCollectedHandler`), within the use case like any event.
- The receiving context subscribes to the integration event and translates it into its own words (`AttachCollectedContactHandler` builds its `PersonId` and `OrganizationId`).
- The shared kernel keeps the identities and the bases of the domain events only. `BoundedContextsTest` lets a context of the core use nothing of another one but its `Application\Published\Event`; the queries still go through a port.

## Consequences

- An emitting context changes its domain events without breaking the others; changing an integration event is a visible change of contract, covered by the scenarios of the reactions.
- One integration event and one translating handler per event crossing contexts.
- Deployed apart, the integration events become the messages of a broker with the same contract, shipped as a contract package with the published queries (0045).

## Alternatives considered

- Keeping the domain events crossing contexts in the shared kernel, as before: the model of a context was the contract of the others.
