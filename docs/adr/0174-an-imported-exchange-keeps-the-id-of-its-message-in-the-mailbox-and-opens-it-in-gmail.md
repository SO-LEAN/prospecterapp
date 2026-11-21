# 0174. An imported exchange keeps the id of its message in the mailbox and opens it in Gmail

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/Connectors/Contract/Event/EmailExchanged.php`, `packages/core/src/Connectors/Application/ImportMail`, `packages/core/src/Prospecting/Domain/Model/ImportedEmail.php`, `packages/core/src/Prospecting/Application/{ExchangeInTimeline,ConsultExchange/ExchangeDetail}.php`, `packages/core/features/reconnaitre-un-echange-importe.feature`, `apps/api/src/Prospecting/Persistence/{ExchangeDocument,DynamoExchangeDetailRetriever,DynamoTimelineRetriever}.php`, `apps/api/src/Exchanges/ExchangeDetailPresenter.php`, `apps/api/src/Shared/Messaging/IntegrationEventTypes.php`, `apps/api/config/subscriptions.json`, `apps/front/src/features/exchanges/components/{exchange-line,exchange-detail}.tsx`

## Context

A member wants to tell an exchange imported from Gmail (ADR 0154) from one noted by hand, and to open the e-mail it comes from in Gmail, to read the whole conversation or answer it (#187). The exchange kept where it comes from, the provider and the identity of the e-mail, the hash of its `Message-ID` (0154): nothing Gmail opens. The ticket proposed to keep the `Message-ID` itself and to open Gmail on a search `rfc822msgid:`, so that the e-mail opens in the mailbox of whoever clicks.

## Decision

- The exchange imported keeps, with where it comes from, the id Gmail gives its message in the mailbox that imported it (`ImportedEmail::$providerMessageId`), opaque, which the import already reads; the identity of the e-mail stays the hash of its `Message-ID` (0154). Connectors tells it in `EmailExchanged`, published as `EmailExchanged.v2`; `.v1` is still read, without it.
- Every reader of the account sees where an exchange comes from, in the timelines and in its detail (`importedFrom`, the provider). The detail links to the message in Gmail, `https://mail.google.com/mail/#all/<id>` (`openInMailbox`, opened in a new tab), to those who manage the personal data only, the presenter deciding it as it does for the campaign of an exchange (ADR 0081, 0152): opening an e-mail is no gesture of the exchange, so no `ExchangeIntent`.
- An exchange imported before has no id of its message: no link.

## Consequences

- The id of a message names it in the mailbox that imported it only: a member who manages the personal data but did not import the e-mail opens their own Gmail on a message it does not hold.
- Gmail opens in the Google account the browser signs in first (`/mail/`, without an account number): a member signed in to several accounts may land in another mailbox.
- Prospecting keeps no `Message-ID` in clear: what an e-mail says stays in the mailbox (0154).

## Alternatives considered

- The `Message-ID` kept in clear, Gmail opened on a search `rfc822msgid:` in the mailbox of whoever clicks, as the ticket proposed: the maintainer chose the id of Gmail of the message, keeping only the hash of the `Message-ID` (2026-11-21).

## Revisit when

Members other than the one who imported an e-mail need to open it, or another provider of mailboxes is imported.
