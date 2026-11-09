# 0096. No activity journal: an audit is designed when it is needed, with its purpose, its events, its retention and its readers

- Date: 2026-11-09
- Type: architecture
- Status: Accepted
- Supersedes: for the activity journal, [0043](0043-a-domain-event-per-intention-and-an-activity-journal.md); for the journal written with the use case, [0095](0095-a-command-writes-its-aggregate-and-its-outbox-all-at-once.md)
- Implemented by: `apps/api/src/Shared/Command/PurgeJournalCommand.php`, `apps/api/src/Shared/Persistence/FormerJournal.php`, `apps/api/src/Shared/Messaging/EmittingContext.php`, `apps/api/tests/Api/NoActivityJournalTest.php`

## Context

`ActivityJournal` wrote every domain event, its identities only, under `JOURNAL#<subject>` in the table of its context, for ever. Nobody read it: no screen, no obligation. The reasons of 0043 no longer held: the figures will be kept by a context of reporting, the GDPR access export is made from the current state, and an audit is no need today. It kept personal data, who acted on which persons, with no purpose and no retention, against the purpose limitation, the data minimisation and the storage limitation of the GDPR. It also added an item per domain event to the transaction of every command (0095), whose ceiling is 100 writes.

## Decision

- No activity journal: a domain event is published to the handlers that react to it, never written down for itself. The domain events stay (0043).
- Its items are deleted from the tables of every instance by `app:journal:purge` (a scan of each table, filtered on `JOURNAL#`, then deleted in batches; `--dry-run` counts them), run on each instance once deployed, then removed.
- The context an event is emitted by, read from its namespace, is kept for the outbox, which writes an event in the table of that context: `Shared\Messaging\EmittingContext`.
- An audit, when a need of one appears, is designed then: its purpose, the events it needs, its retention and its readers, and it subscribes to those events like any other handler.

## Consequences

- No item is written for the events any more: a command writes only its state and what follows from it.
- The history of the changes made in the application is lost, the former versions of a corrected exchange with it (only its last correction is kept, `Correction`).
- The purge command lives until it has run on every instance, then a later change removes it.

## Alternatives considered

None: the need was stated as the removal.

## Revisit when

A need of audit, of history or of proof appears: it is designed then, as the decision says.
