# 0176. The Gmail history of a correspondent is caught up asynchronously through the import, its address read and never kept

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/Connectors/Application/{CatchUpMailHistory,ConsultMailCatchUpIntents}`, `packages/core/src/Connectors/Domain/Model/Connection.php` (`catchUpMailWith()`), `packages/core/src/Connectors/Domain/Port/{CorrespondentAddresses,ProviderMailbox}.php`, `packages/core/src/PersonalData/Application/WhichEmailsHaveTheseContacts`, `packages/core/src/Prospecting/Application/WhichEmailsHaveTheseOrganizations`, `packages/core/features/rattraper-l-historique-gmail.feature`, `apps/api/src/Connectors/Controller/{CatchUpContactGmailController,CatchUpOrganizationGmailController}.php`, `apps/api/src/Connectors/AntiCorruption/PersonalDataAndProspectingCorrespondentAddresses.php`, `apps/api/src/Connectors/Google/GoogleMailbox.php`, `apps/front/src/features/exchanges/components/catch-up-gmail.tsx`

## Context

The import of Gmail (0154) notes only what the mailbox notifies once it is active: the e-mails already exchanged with a contact added since, or before the import, stay out of the timeline. The member asks, from the card of a contact or of an organization, to catch them up. The import of a message is asynchronous (a reaction reads it, another notes it), and only Prospecting knows whether an e-mail is new, noted before or erased (0166); reading up to 200 messages in the request of the API takes seconds. The address of a contact is a personal data PersonalData keeps (0031), and Connectors published no way to read it: it only asked which contact an address designates, the address never leaving the question (0154).

## Decision

- **Asynchronous, nothing counted** (the maintainer, 2026-11-21): the command `CatchUpMailHistory` searches the mailbox of the member for the messages exchanged with the address of the correspondent, from it or to it, over the 12 months before, the 200 newest, but the spam, the trash and the drafts (one search of Gmail, `{from: to:}`), and gives each to the import as a message added (`MailboxMessageAdded`, an id, the outbox one item). The rules of the import keep the sender and the recipients in « To » only, and note an e-mail once, never again once erased. The endpoints answer 202 without body; the screen says the catch-up started and reads the timeline again once the reactions ran.
- **The address read, never kept**: Connectors reads it through its port `CorrespondentAddresses`, from two services published on sets, `WhichEmailsHaveTheseContactsService` of PersonalData, answered only to a member reading the contact in clear, and `WhichEmailsHaveTheseOrganizationsService` of Prospecting, for an organization the account prospects; it passes it to Google in the search and writes it nowhere. A correspondent absent from their answer is unknown (404), before any connection is looked at.
- **Offered by the core**: only a member allowed to manage the personal data catches up (`PersonalDataManagersOnly`), the API checking the role too, as for the import (0081); the query `ConsultMailCatchUpIntents` offers `CatchUpGmail` to them while their import is active and the correspondent has an address; the API composes it as a link `catchUpGmail` on the contact and on the timeline of the organization.
- An import off or to reconnect refuses the catch-up (`MailImportNotActive`), a correspondent without e-mail too (`NoAddressToCatchUp`); a token the provider refuses makes the import to reconnect, the command succeeding.

## Consequences

- Catching up again, or after erasing an exchange, notes nothing more: no state of the catch-up is kept, the import deciding alone.
- The member is not told how many e-mails were imported: the timeline shows them as they arrive, a second or more later.
- PersonalData publishes the address of a contact for the first time, to a reader in clear only.
- The two endpoints have no tool of the MCP server yet: they wait in `TO_COVER` with the other writes of the import (0163).

## Alternatives considered

Weighed in the plan of the ticket, and decided by the maintainer:

- The command counting what it gives the import (reading each message in the request, asking Prospecting which are noted): the toast says how many, but a command answering data goes against 0029, the request lasts seconds, and the count is a forecast.
- A catch-up tracked, its id carried through the import to a read model of Prospecting counting the exchanges noted, the screen polling it: an exact count, but a read model, a change of the contract of `EmailExchanged` and polling for a number.

## Revisit when

The members ask how many e-mails a catch-up imported, or want another period than the 12 months, or a catch-up of the whole mailbox.
