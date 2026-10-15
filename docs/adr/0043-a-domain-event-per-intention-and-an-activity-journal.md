# 0043. Every intention records a domain event; an activity journal keeps them all

- Date: 2026-10-15
- Status: Accepted
- Implemented by: `packages/core/src/SharedKernel/Domain/{ReleasesEvents.php,RecordsEvents.php,Event}`, `packages/core/src/*/Domain/Event`, `SharedKernel\Application\PublishesEvents`, `apps/api/src/Shared/Persistence/Journal`, `packages/core/tests/Architecture/EventHandlersSubscribedTest.php`

## Context

0042 brought domain events for the contacts only: some intentions recorded one, most did not, and each use case published in its own way. The user wanted the concept general, every aggregate recording its events even when nobody listens yet, and a first reader of them all: an activity journal, for the GDPR access export and the audit to come, and for the read models a store like DynamoDB will need.

## Decision

- Every successful intention records one domain event (two when it also leaves contacts alone), named in the past tense: `Relocated`, `PersonRenamed`, `AccountLogoChanged`... A named constructor used by a use case records its creation (`ProspectingStarted`, `ContactCollected`).
- An event extends `DomainEvent` (account, member who did it, moment, `subject()`), through `OrganizationEvent`, `PersonEvent` or `AccountEvent`. It carries identities only, never personal data, so that it may be kept after an erasure.
- An event lives in `<Context>\Domain\Event`; only the events another context handles are in `SharedKernel\Domain\Event`, so that the published language stays small.
- An aggregate `implements ReleasesEvents` and `use RecordsEvents` (the stack, `recordThat()`, `releaseEvents()`). A handler `use PublishesEvents` and calls `publishWhatHappenedTo($aggregate)` after saving it.
- The API keeps every event in a `journal` table of the database of its subject's context (`ActivityJournal`, a Messenger handler of `DomainEvent`), with no screen yet.
- The scenarios check the side effects of the reactions, not the events: every `EventHandler` of the core runs in them (`EventHandlersSubscribedTest`), and the intents tests check that an accepted intention records an event and a refused one none.

## Consequences

- Who did what, when, to which organization, person or account is known from now on.
- About twenty event classes, one line per intention method, and a call per handler that saves; forgetting the call loses the events of that use case (the scenarios only notice it when a reaction depends on it).
- The journal grows with every change; nothing purges it yet.

## Alternatives considered

- Publishing explicitly in each handler (`$this->events->publish(...$aggregate->releaseEvents())`): the user preferred a trait of the shared kernel naming the gesture.
- Recording events only where a context listens: the user wanted the concept general.

## Revisit when

- A screen needs the history (it will read the journal), the journal needs a retention, or the events must leave the request (outbox, asynchronous transport).
