# 0190. Stored and exposed names are consistent English

- Date: 2026-11-22
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/tests/Architecture/BackedEnumValuesTest.php`, `docs/architecture/persistence.md`, `docs/architecture/messaging.md`, `docs/architecture/api.md`

## Context

After the prod release and the contact import, renaming a stored value, an attribute, a key or an event type needs a data migration. A review before that release found the names mostly English, with a few gaps:

- Enum values mixed casings: `videoCall` and `asIs`, but `linkedin-company` and `welcometothejungle` for the link sources.
- A few calques of French: "civility" for a contact's title, "make conform" for redacting the exchanges of a forgotten contact (ADR 0079), "catch up" for reimporting e-mails from Gmail, and the gerunds `starting`, `freezing` and `stopping` in the campaign routes.
- Two domain events carried those calques in their type: `CivilityChanged`, `ExchangeMadeConform`.
- The campaign message variables were French: `{civilité}`, `{prénom}`, `{nom}`, `{organisation}`, `{fonction}`.

## Decision

- **Enum values.** A stored or exposed text value of an enum is its case name in lower camelCase: `VideoCall` is `videoCall`. The roles keep Symfony's `ROLE_*`. `BackedEnumValuesTest` checks every text-backed enum of the core, where all stored and exposed enums live. `App\SignIn\OAuth\SealPurpose` is exempt: it is sealed inside an opaque OAuth state or code, never stored, never read by a client. The link sources become `linkedInProfile`, `linkedInCompany`, `malt` and `welcomeToTheJungle`; a link to any other site keeps its host as its source.
- **Event types.** An event type is the class's short name, in English, in the past tense, then its version: `ExchangeFactsChanged.v4`. `CivilityChanged.v1` becomes `TitleChanged.v1` and `ExchangeMadeConform.v1` becomes `ExchangeRedacted.v1`. No subscription listens to either, so no EventBridge rule changes.
- **DynamoDB.** Every table keeps the key attributes and the index names `Tables` creates (`PK`, `SK`, `GSI1PK`…, `NAME`, `EMAIL`, `PHONE`). Item attributes are lower camelCase English. Key prefixes are UPPER_SNAKE English nouns. The person attribute `civility` becomes `title`, the exchange attribute `madeConform` becomes `redacted`, and the prefix `TO_MAKE_CONFORM#` becomes `TO_REDACT#`. The other names already followed the rule; `AGENDA#` (a member's planned exchanges) and `CALENDAR#` (their calendar) are two meanings, not two spellings.
- **API.** Routes and fields use the English words: "title" for how one addresses a contact, "redact" for the exchanges of a forgotten contact, "reimport" for Gmail.
  - `PATCH /api/contacts/{id}/title`, link `changeTitle`, field `title`, refusal `invalidTitle`.
  - `GET /api/organizations/{id}/exchanges-to-redact`; fields `toRedact`, `textToRedact`, `redacted`; link `redact`; refusal `nothingToRedact`.
  - `POST /api/contacts/{id}/gmail-reimports` and `POST /api/organizations/{id}/gmail-reimports`, link `reimportGmail`, refusal `noAddressToReimport`.
  - `POST /api/campaigns/{id}/start`, `/freeze` and `/stop`: verbs, like the links that already had these names.
  - The MCP parameters that mirror these inputs follow (`title`, `redacted`); the tool names stay those of ADR 0189.
- **Campaign messages.** The variables are `{title}`, `{firstName}`, `{lastName}`, `{organization}` and `{jobTitle}`, in the casing of the stored names. The tag example of the API is `Café owners`, written #CAFE_OWNERS.
- **No migration.** Prod holds no data yet, and the dev instance is recreated after the deploy. Nothing reads the former names back: the former event types are not kept readable (`FORMER_TYPES`), and messages already written keep their French variables, which now stay as written.
- Internal names (classes, commands, the `Civility` enum, `ConsultExchangesToMakeConform`) follow ADR 0188: they are renamed when a branch touches them. The classes whose short name is the stored or exposed name were renamed here: the two domain events and the three refusals.

## Consequences

- After the deploy, dev is recreated: its tables are emptied, the demonstration is written again, the QA members are registered again, Google is connected again, and the failure queues of the relay and the worker are emptied, so that no event under a former type comes back as `UnreadableEvent`.
- A new enum with a value in another casing fails `BackedEnumValuesTest`.
- The rule in `docs/architecture/messaging.md` that keeps a former event type readable while an outbox or a failure queue may hold it still applies to later changes; it did not apply here because nothing holds the former types.

## Alternatives considered

- A one-shot console command migrating the stored values, attributes and keys, with the queued events drained or migrated before the switch. Rejected by the maintainer: prod holds no data, and recreating dev costs less than writing and testing a migration run once.
- `{civility}` for the message variable. Rejected by the maintainer: "civility" is the false friend this record removes; English says title.

## Revisit when

Prod holds data: from then on, a rename comes with a migration.
