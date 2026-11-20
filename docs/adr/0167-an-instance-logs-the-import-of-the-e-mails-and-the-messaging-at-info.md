# 0167. An instance logs the import of the e-mails and the messaging at info

- Date: 2026-11-20
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/config/packages/monolog.yaml` (the channels `mailbox` and `messaging`, the handler `traced`), `apps/api/src/Connectors/Google/GoogleMailbox.php`, `apps/api/src/Connectors/AntiCorruption/PersonalDataAndProspectingCorrespondents.php`, `apps/api/src/Connectors/MailWatchesRenewer.php`, `apps/api/src/Shared/Messaging/{IntegrationEventBus,IntegrationEventConsumer}.php`, `apps/api/tests/Shared/LogChannelsTest.php`

## Context

On an instance, the handler of Monolog is `fingers_crossed` at `error`: a line at info or warning is written only when an error follows in the same invocation. A reply of a contact received in Gmail was never imported on dev, the notification received, and nothing told whether the reply was read and set aside, or never read: the import of the e-mails (ADR 0154) logs nothing without an error, nor do the relay and the worker, whose lines were said to carry the correlation of each gesture in CloudWatch.

## Decision

Two channels are written at info on an instance, on stderr in JSON, by a handler of their own beside `fingers_crossed`, which keeps them too, so that an error of theirs still writes the lines the other channels kept before it: `mailbox`, the import of the e-mails (each reading of a history, its points and the ids of the messages it listed; each message read, its id at the provider and its labels, or why it is set aside; a sender nobody of the account has; the renewals of the watches), and `messaging`, the relay and the worker (each event published, each event not applied). Every other channel stays as it was. These lines carry no personal data: never an address, a subject, a text or a name, only identities, labels and points of a history.

## Consequences

- An e-mail not imported is followed on an instance: the line of a reading of the history lists the ids of the messages it listed; the reaction importing one message writes, one after the other in the log stream of its invocation, the line of the message read (its id, its labels) and, when its sender is nobody of the account, the lines of no contact then of no organization (by the account: the adapters asking PersonalData and Prospecting know no message). A gesture is followed from its command to its last reaction by its correlation in the logs of the relay and the worker.
- Before an error, the lines of these channels are written twice: once at info, once with the others kept before the error.
- CloudWatch receives a line per event published and per message read: a cost on the relay, the worker and the API, against nothing before an error.
- A service of these channels logs no personal data at info: a new line is written as such, by identity.

## Alternatives considered

- Only the channel of the import, the messaging left as it was: weighed by the maintainer (2026-11-20), refused because the relay and the worker would still log nothing without an error, the correlation in CloudWatch still untrue.
- Every channel at info: weighed by the maintainer, refused for its cost, the other channels telling nothing the failures and the errors do not.

## Revisit when

The cost of CloudWatch on an instance grows with the volume of the events, or a line of these channels needs a personal datum.
