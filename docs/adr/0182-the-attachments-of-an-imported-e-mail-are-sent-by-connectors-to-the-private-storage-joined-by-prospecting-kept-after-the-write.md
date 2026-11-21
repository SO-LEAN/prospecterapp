# 0182. The attachments of an imported e-mail are sent by Connectors to the private storage, joined by Prospecting, kept after the write

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Supersedes: for the wait of the rebuild of the figures, a minute longer than the worker runs (28 s), [0104](0104-reporting-keeps-its-figures-by-additions-its-rebuild-run-from-the-console.md)
- Implemented by: `packages/core/features/importer-les-pieces-jointes-d-un-e-mail.feature`, `packages/core/src/Connectors/Domain/Model/MailboxMail.php`, `MailboxAttachment.php`, `packages/core/src/Connectors/Application/ImportMail`, `packages/core/src/Prospecting/Application/NoteImportedEmail`, `KeepImportedAttachment`, `apps/api/src/Connectors/Google/GmailMessage.php`, `GoogleMailbox.php`, `apps/api/infra/messaging.tf`

## Context

An e-mail imported from Gmail (0154) becomes an exchange with its subject and its text; its attachments, a quote, a CV, stay in Gmail. An exchange holds 10 files of 10 MB at most, private files of the storage sent as uploads, kept once joined (0077); the API already sends a file it downloaded with the form of an upload (0172). The integration events travel in an item of outbox (400 KB) and on the bus (1 MB a put): no file goes there. The reactions are delivered at least once and must be idempotent; an upload kept is no upload any more. The worker ran 28 s at most with 1,024 MB; an e-mail sent to several contacts may bring 10 files of 10 MB for each of them.

## Decision

- **Which files**: the adapter of Gmail reads as attachments the parts with a file name, but an image embedded in the body (a part named by a `Content-ID` and not given as an attachment, the logo of a signature); a file shown inline without one (Apple Mail) is attached.
- **Connectors sends them**: `ImportMail` keeps the first ten of 10 MB at most, in their order, the others left out, its own limits never imported from Prospecting; each is read once from Gmail (`users.messages.attachments.get`) and sent to the private storage with the form of an upload, once for each correspondent recognized (`ProviderMailbox::depositAttachment()`): each exchange keeps a file of its own. The core never holds the bytes.
- **The event carries their references**: `EmailExchanged.v3` names, for its correspondent, the upload, the name and the size of each file sent, and the name and the size of each file left out; `.v1` and `.v2` still read. The names travel with the subject and the text, under the exception of 0154.
- **Prospecting joins them**: `NoteImportedEmail` measures each upload and joins it to the exchange it notes (`Exchange::importAttachment()`, an identity derived from the exchange and the upload, `AttachmentId::ofImportedEmail()`), by the member whose mailbox imported it; the text of the exchange ends with a line per file it does not hold, left out or no longer in the storage: « Pièce jointe non importée : video.mp4 (25 Mo), à ouvrir dans Gmail ».
- **Kept after the write**: the reaction `KeepImportedAttachment` keeps the upload once the exchange is written (`AttachmentImported`), an upload no longer there being nothing to do. `NoteImportedEmail` tried again after a failed commit finds its uploads; an exchange never written (noted before, noted meanwhile by another mailbox, its contact forgotten) keeps none, its uploads going after a day.
- **The worker runs 120 s** (`infra/messaging.tf`, and locally), its memory unchanged: an e-mail of 10 files of 10 MB is read once and sent to each correspondent within it. The rebuild of the figures waits two minutes and a half for the recordings in flight (`RecordingsOfTheWorker`), longer than the worker runs.

## Consequences

- The member finds the files of an e-mail on its exchange, hidden like any attachment without the right to manage the personal data (0078), erased with it.
- Between the write of the exchange and the keeping of its uploads, a few seconds, an attachment is listed whose download reads nothing yet.
- A file heavier than 10 MB, or beyond the tenth, is never downloaded: the member opens it in Gmail.
- An e-mail sent to many contacts with heavy files may still exceed 120 s: it fails three times and waits in the queue of the failures, its text with it.
- `app:reporting:rebuild` and `make db` wait three minutes more: the rebuild waits twice for the recordings in flight.

## Alternatives considered

- The files kept by `NoteImportedEmail` before the write, as `AttachFile` does: not idempotent, a retry after a failed commit finding its uploads kept and losing them, the kept files left without exchange; raised by the architect of the plan.
- For the time of the worker, weighed by the maintainer (2026-11-21): a reaction per file sent, one more step and event, the exchange waiting for its files; a cap on the total of an e-mail, a business rule the ticket has not; the maintainer chose reading each file once and a worker of 120 s.

## Revisit when

E-mails to many contacts with heavy files fail by time, or a second mailbox (Outlook) comes.
