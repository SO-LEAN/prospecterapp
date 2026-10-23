# 0079. The exchanges of a forgotten contact stay in the timelines of their organizations, to make conform

- Date: 2026-10-23
- Status: Accepted
- Supersedes: for the exchanges of a forgotten contact, the erasure of the personal data by the forgetting of [0031](0031-personal-data-context.md), left open by [0074](0074-an-exchange-with-a-contact-is-an-aggregate-of-prospecting.md) and [0077](0077-the-attachments-are-private-files-reached-by-signed-urls.md); for the exchanges to make conform of an organization, the rule of [0049](0049-lists-in-slices-after-a-cursor.md) that a list is never counted; for declaring an exchange made conform, the one intent per use case and per endpoint of [0025](0025-one-business-intent-per-use-case-and-endpoint.md): it is declared at once by the correction of its text or of its facts (`madeConform`)
- Implemented by: `packages/core/src/Prospecting/Domain/{Exchange,ContactPresence,ExchangeIntent}.php` (`forgetItsContact`, `declareConform`, `contactPresenceIn`, `isToMakeConform`), `packages/core/src/Prospecting/Application/{KeepExchangesOfForgottenContact,ConsultExchangesToMakeConform}`, `packages/core/features/les-echanges-d-un-contact-retire-ou-efface.feature`, `apps/api/src/Prospecting/Persistence/DynamoExchangeRepository.php`, `apps/api/src/Contacts/ShowExchangesToMakeConform.php`, `apps/front/src/features/exchanges`

## Context

When a contact leaves an organization, or when their card is erased (forgotten on request, removed from their last organization, their only organization no longer prospected), the account must not lose the commercial history of its organizations. The forgetting (GDPR) erases the person in PersonalData; the text and the files of their exchanges, kept by Prospecting (0074, 0077), may hold their personal data too, and nothing tells which part of them.

## Decision

- A forgotten contact leaves their exchanges in the timelines of their organizations: `KeepExchangesOfForgottenContact` reacts to `ContactForgotten` and marks each exchange (`Exchange::forgetItsContact()`, recorded by `ExchangeContactForgotten`), by the member who erased the contact. The text and the files are kept, as the legitimate interest of the account in its commercial history, once made conform.
- Such an exchange is to make conform: its text until a prospector declares it made conform with a correction of its text or of its facts (`madeConform`, the intent `MakeConform`, carried by those two commands and their endpoints rather than one of its own: the prospector retouches the text and declares it conform in one gesture, a correction declaring it while nothing is to make conform being refused whole, `NothingToMakeConform`, open to every prospector of the account like the correction of the text, 0078, recorded by `ExchangeMadeConform` with who and when), its files until they are removed. The client shows its contact as "Contact effacé", without a card.
- A contact removed from an organization but still known is read as such in the timeline of that organization (`ContactPresence::Removed`, from the contacts of the organization at the moment of the reading): nothing is recorded, a contact added again is present again.
- The timeline of an organization tells how many of its exchanges are to make conform (`toMakeConform`), and lists them alone, a slice after the other (`ConsultExchangesToMakeConform`, `GET /api/organizations/{id}/exchanges-to-make-conform`). In the table of Prospecting (0048), a link `EXCHANGES#<organization>` / `EXCHANGE#<id>` of an exchange to make conform is also listed in `GSI2` by `TO_MAKE_CONFORM#<organization>`, under the position of the exchange; DynamoDB counts them there without reading them.

## Consequences

- The account keeps personal data of a forgotten person until a prospector makes each exchange conform: the timeline of each organization signals them, nothing forces it.
- A count is read with every timeline of an organization: a few exchanges to make conform per organization, not a list a client pages through.
- The exchanges forgotten before this decision, only on the dev instances, are not marked: their contact reads as removed.

## Alternatives considered

None: the ticket asked for the exchanges kept, marked, and a badge counting them.

## Revisit when

- An account needs the list of every exchange to make conform, across its organizations, or the texts must be made conform automatically.
