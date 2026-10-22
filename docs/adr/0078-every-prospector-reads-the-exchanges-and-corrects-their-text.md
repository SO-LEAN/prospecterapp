# 0078. Every prospector reads the exchanges, their text flagged without the right, and may correct it

- Date: 2026-10-22
- Status: Accepted
- Supersedes: for reading the exchanges, the right to manage personal data of [0074](0074-an-exchange-with-a-contact-is-an-aggregate-of-prospecting.md) and [0076](0076-the-timeline-of-an-organization-gathers-its-exchanges-and-those-of-its-contacts.md)
- Implemented by: `packages/core/src/Prospecting/Domain/{Exchange,ExchangeIntent,Prospector}.php` (`isTextFlaggedFor`, `correctText`), `packages/core/src/Prospecting/Application/CorrectExchangeText`, `packages/core/features/corriger-le-texte-d-un-echange.feature`, `apps/api/src/Exchanges/CorrectExchangeText.php`, `apps/front/src/features/exchanges/components/{exchange-text,text-correction-form}.tsx`

## Context

A member without the right to manage personal data, an assistant, needs the history of the exchanges for the commercial follow-up, and must be able to make the text of an exchange conform to the GDPR. The names of the persons are already masked for them by PersonalData (`Masking`); the text of an exchange, kept by Prospecting (0074), cannot be masked: nothing tells which part of it is personal data.

## Decision

- Every prospector of the account reads the timelines of the contacts and of the organizations, and the detail of an exchange: `TimelineIntent::ConsultTimeline` is offered without the right, `NoteExchange` still needs it. The names of the interlocutors and of the members are read from PersonalData as for any card, masked without the right.
- The text is read as it is, flagged to a prospector without the right (`textFlagged` of the responses, `Exchange::isTextFlaggedFor()`): it may hold personal data.
- Correcting the text is its own intent, `CorrectText` (`CorrectExchangeText`, `PATCH /api/exchanges/{id}/text`, the link `correctText`), open to every prospector of the account and recorded by `ExchangeTextCorrected`, with who corrected it last like any correction. Correcting the other facts (`CorrectExchange`) and erasing an exchange still need the right.

## Consequences

- An assistant reads in clear what a prospector wrote in a text, names included: the flag warns them, the conformity of the texts rests on those who write them and on the correction of the text.
- The attachments of an exchange will be hidden to a prospector without the right, only their number shown.

## Alternatives considered

None: the ticket asked for the text read and flagged, its correction alone open without the right.

## Revisit when

- The texts must be masked automatically, or a prospector must be given a partial right (noting without reading).
