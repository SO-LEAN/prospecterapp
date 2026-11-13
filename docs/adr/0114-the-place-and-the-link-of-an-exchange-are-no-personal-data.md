# 0114. The place of a meeting and the link of a video call are no personal data: every prospector corrects them

- Date: 2026-11-13
- Type: domain
- Status: Accepted
- Supersedes: for the place and the link of an exchange, the right to manage personal data needed to correct its facts of [0078](0078-every-prospector-reads-the-exchanges-and-corrects-their-text.md) and [0081](0081-the-api-checks-the-access-the-core-keeps-the-state.md)
- Implemented by: `packages/core/src/Prospecting` (`ExchangeFacts`, `VideoCallLink`, `Exchange::correctVenue()`, `ExchangeIntent::CorrectVenue`, `CorrectExchangeVenue`, `ProposeMeetingPlace`), `features/planifier-un-echange.feature`, `features/corriger-le-lieu-ou-le-lien-d-un-echange.feature`, `apps/api/src/Exchanges` (`CorrectExchangeVenueController`, `ExchangeDetailPresenter`), `apps/front/src/features/exchanges`

## Context

A meeting has its place, written freely, and a video call its link, which may come once it is planned. Correcting the facts of an exchange needs the right to manage personal data (0078, 0081), and a planned exchange is not corrected as a whole (0111). The place and the link need a gesture of their own. A place written freely or a link may name a person (« chez Mme Lovelace », a personal Zoom room): whether they are personal data decides who corrects them, whether they are flagged to a reader without the right, and whether they are to make conform once the contact is forgotten (0079).

## Decision

- The place of a meeting and the link of a video call are no personal data: where and how to meet, not who.
- Every prospector of the account reads them in clear and corrects them, planned or done, by their own gesture (`CorrectExchangeVenue`); an exchange corrected as a whole keeps them while its channel has one.
- They are not to make conform once the contact is forgotten: the text and the files stay what is.

## Consequences

- An assistant adds the link of a video call planned by a colleague, or corrects the place of a meeting, without the right.
- A place or a link naming a person is read by every prospector, and stays once the contact is forgotten; it is up to the prospectors to write a place, not a person.

## Alternatives considered

Correcting them with the right to manage personal data only, like the other facts (0078, 0081), was weighed by Michel and refused: the place and the link are logistics every member of the team needs to reach the exchange.

## Revisit when

A place or a link is found to hold personal data the account must erase with its contact.
