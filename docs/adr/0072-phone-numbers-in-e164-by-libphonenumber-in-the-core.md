# 0072. Phone numbers are kept in E.164 by libphonenumber, inside the core; a call is recognized by its number

- Date: 2026-10-18
- Status: Accepted
- Implemented by: `packages/core/src/SharedKernel/Domain/PhoneNumber.php`, `packages/core/composer.json`, `RecognizeCallingOrganization`, `RecognizeCallingContact`, `apps/api/src/Contacts/RecognizeCaller.php`, the index `PHONE` (`apps/api/src/Shared/Persistence/{SearchIndexes,Tables}.php`, `apps/api/infra/tables.tf`)

## Context

A phone number was a string kept as typed: "03 88 11 22 33", "0388112233" and "+33 3 88 11 22 33" were three numbers. A mobile application is planned that, on an incoming call, asks Prospecter whom the number belongs to and opens the card: a number must have one value, and be found by it.

## Decision

- The core reads and checks a number with `giggsey/libphonenumber-for-php`, the PHP port of the library of Google: `PhoneNumber` reads the national format of France or the international one, refuses what is no valid number (a violation of `phoneNumber`), is kept in E.164 (`+33388112233`) and written in the international format (`+33 3 88 11 22 33`). The library is part of the hexagon, an exception to the core requiring only PHP and `intl`: pure PHP, no infrastructure, and the rules of numbering are not ours to keep.
- An organization and a person carry their number as the key of the index `PHONE` of their table, within the scope of their search (0071). `RecognizeCallingOrganization` (Prospecting) and `RecognizeCallingContact` (PersonalData) answer who has a number among the organizations an account prospects and its contacts; the second needs the right to manage personal data, a number read being a personal data. `GET /api/phone-numbers/{phoneNumber}` composes both.
- A number kept before is read as it was when it is no valid number, and kept in E.164 when the aggregate is saved again (`app:search:rebuild` saves them all).

## Consequences

- A number typed in any format of France or with its country code is found by the number calling.
- Numbers of other countries must be typed with their country code.
- The core has a runtime dependency to upgrade with its metadata (numbering plans change).

## Alternatives considered

- A port in the core implemented by the API with the library: the core would keep to PHP and `intl`, but the rule of what a number is would leave the hexagon and every scenario would double the port; Michel chose the library inside the core.
- Reading a national number with the country of the address or of the language of the card: closer for foreign numbers, but the same digits could become two numbers depending on the card; France by default was chosen.
- Normalizing without refusing: a number not read would never be recognized.

## Revisit when

- The members prospect mostly outside France: the country of reading becomes a setting of the account.
