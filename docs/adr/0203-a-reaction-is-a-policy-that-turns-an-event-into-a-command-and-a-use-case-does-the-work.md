# 0203. A reaction is a policy that turns an event into a command, and an ordinary use case does the work

- Date: 2026-11-23
- Type: architecture
- Status: Accepted
- Supersedes: for the reaction as an event handler of the context that reacts, [0046](0046-integration-events-between-contexts.md); for the subscription naming its handler, [0097](0097-events-travel-as-eventbridge-events-reactions-are-subscriptions.md); for the worker calling the reaction through the event decorators, [0101](0101-the-reactions-run-after-the-command-in-a-worker.md); for a subscription naming a handler per detail-type (`handlers`), [0104](0104-reporting-keeps-its-figures-by-additions-its-rebuild-run-from-the-console.md); for the parent `CalendarToFill`, [0133](0133-the-calendar-entry-keeps-the-state-of-the-push-of-its-exchange.md); for the parent `MailboxToRead`, [0154](0154-gmail-is-pushed-through-a-topic-of-the-google-cloud-of-the-account.md); for the decorators of the event handlers, [0094](0094-a-handler-is-decorated-by-the-decorator-of-its-kind.md)
- Implemented by: `packages/core/src/SharedKernel/Application/Handler/Policy.php`, `packages/core/src/*/Application/Policy`, `packages/core/tests/Double/SynchronousEvents.php`, `packages/core/tests/Architecture/PoliciesDependOnNothingTest.php`, `apps/api/src/Shared/Messaging/{Reaction,PolicyReaction,Subscriptions}.php`, `apps/api/src/Shared/DependencyInjection/SubscriptionsPass.php`, `apps/api/config/subscriptions.json`, `apps/api/tests/Architecture/ReactionsAreSubscribedTest.php`

## Context

The core had 63 event handlers: 12 translators (`Publish…Handler`) and 51 reactions. A reaction mixed two things: the rule "when this event happens, do this intent", and the intent's work (load the aggregate, call an intention method, save it). It ran only from its event, so replaying it from the console meant forging an event. When several events asked for the same reaction, the core added an abstract parent only to route them (`CalendarToFill`, `MailboxToRead`), and #268 had to allow those two by name. The commands and the events had two families of decorators.

ADR 0200 gave the word "policy" the event storming's meaning: when an event happens, and when a condition holds, a command runs.

## Decision

- A **policy** is a final readonly class of `<Context>/Application/Policy`, named `<Intent>Policy`, without a constructor. It implements the marker `SharedKernel\Application\Handler\Policy`. Its `__invoke()` takes the event it listens to, or a union of the events, and returns the command to run, filled. It returns `?Command` when it holds a condition on the event's data, or a list of commands when the event asks for several. It calls nothing. A condition that needs a state or personal data belongs to the use case.
- A reaction's work is an ordinary use case: a command and its `CommandHandler`, sent through the command decorators. Any client may send it: a controller, a console command, an MCP tool, a data recovery.
- One adapter delivers an event to the policies listening to it and sends their commands: `PolicyReaction` in the API (the worker and the `sync` delivery), `SynchronousEvents` in the scenarios. The worker's inbox, unit of work, failures and depth do not change: the command's unit nests in the worker's.
- A subscription of `subscriptions.json` names its `policy`. Its events are those the policy's signature takes, and `ReactionsAreSubscribedTest` checks it. Subscriptions keep their names, sources, events, payload patterns and inboxes: nothing changes on the bus.
- The translators stay the only event handlers. They run within the command that publishes, inside its decorators, so `RefusalTranslatingEventHandler` and `TransactionalEventHandler` go once no reaction is an event handler.
- `CalendarToFill` and `MailboxToRead` go: a union type says which events a policy takes.
- The contexts move one pull request at a time, from the least to the most touched: Accounts, Booking and Reporting, then Campaigns, Connectors, PersonalData, then Prospecting in two. Until the last, a reaction not moved yet is still an event handler, reached through its decorators.

## Consequences

- Every use case runs the same way, whoever sends it, and a reaction's work can be replayed without an event.
- A policy is tested alone, without a double: an event in, the command (or none) out.
- A command per reaction adds a class per reaction, and some commands copy an event's payload (Reporting's facts).
- Until the last context moves, two kinds of reactions live side by side, and the documents describe both.

## Alternatives considered

The maintainer chose among the options the plan listed:

- The translators keep their two decorators: two families of decorators would stay for no reason, since a translator always runs inside a command's.
- The translators become a step of the command: every handler that publishes facts would then depend on the retrievers.
- A named method on the policy rather than `__invoke()`: a second convention beside the handlers, which the adapters already find by the type of `__invoke()`'s argument.
- A policy named `When<Event><Intent>`: it reads badly with a union of events.
- The marker in a new role folder of the shared kernel rather than `Handler`: it would extend the roles ADR 0201 lists, for one interface.

## Revisit when

A reaction needs a state that its event does not carry, and that no use case can check, to decide whether it runs. Or a translator needs to refuse.
