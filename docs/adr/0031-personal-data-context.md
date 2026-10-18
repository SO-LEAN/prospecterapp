# 0031. A PersonalData context keeps the personal data of natural persons, and decides who reads them

- Date: 2026-10-15
- Status: Accepted, port names superseded by [0045](0045-published-language-between-contexts.md), the text of the exchanges kept by Prospecting since [0074](0074-an-exchange-with-a-contact-is-an-aggregate-of-prospecting.md)
- Implemented by: `packages/core/src/PersonalData`, `apps/api/src/PersonalData`, `apps/api/src/Me`

## Context

Personal data were spread over the contexts: the name, email, phone and picture of a member lived in `Member` (Accounts), and the contacts of the prospected organizations were about to come. The user wanted one context to hold the personal data of individuals (members, contacts, customers), always called for them, never copied into another context, in charge of who may read them (in clear or masked) and, later, of the GDPR requests (access, anonymization). An email was unique across accounts, members and prospected organizations, a rule inherited from the former single table of persons that spanned the contexts.

## Decision

- `PersonalData` keeps a `Person` per natural person, for one account and one purpose (`Membership`, `Prospecting`): Prospecter is the processor of each account, the persons of two accounts are never shared, even for the same individual.
- The other contexts keep a `PersonId` only, never a copy (no local projection): `Member` keeps its person id; the API composes resources by asking PersonalData, one call for a person (`ConsultPerson`) or one for a page (`ConsultPersons`).
- The aggregate decides the reading, from a `Reader` (a member, its account, its person, its right) read through the port `Readers` (anticorruption adapter `AccountsReaders` in the API):
  - another account: the person does not exist (404);
  - the person themself, or a member with the right to read personal data (`ROLE_PERSONAL_DATA`, administrators too): in clear;
  - any other member of the account: masked with a fixed number of stars (`G***e`, `g***@***`, `******78`, no picture), so that the length does not leak.
- Changes: a person changes their own data; a member with the right changes the persons their account prospects, never the profile of a colleague (403, `NotAllowed`). The intents offered and the intents accepted are the same rule (`PersonIntentsTest`).
- Emails: unique among the persons of an account for a purpose (PersonalData), and among the organizations of an account (Prospecting); the rule across contexts (`UniqueEmails`) is gone.
- The "my account" page composes Accounts (membership, organization) and PersonalData (profile) in `apps/api/src/Me`, like `Dashboard` composes two contexts.

## Consequences

- One place holds personal data: masking, access and anonymization (step 4) are decided once.
- A page with persons costs one batch call to PersonalData; searching or sorting on personal data will have to go through PersonalData too.
- Two members of the same account cannot share an email; two accounts may know the same person, each with its own record.
- The member's user name still lives in Accounts until the login moves to an email kept by authentication (next step).

## Alternatives considered

- Naming the context `Identity`: kept for authentication; the user chose `PersonalData` (0030).
- One global record per person, shared by the accounts, the access rights filtering who reads it: rejected by the user, it mixes the processing of different accounts.
- Masking with one star per hidden character (`M****l`): rejected by the user for a fixed length that does not leak the length.
- Letting a member with the right change the profile of a colleague: rejected by the user, a profile belongs to its person.
- Keeping a global unique email: rejected by the user for a rule per account.

## Revisit when

The contacts of the prospected organizations come (purpose `Prospecting`), or a search on names has to cross PersonalData.
