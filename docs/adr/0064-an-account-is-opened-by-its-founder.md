# 0064. An account is opened by its founder, from the console until the members open theirs

- Date: 2026-10-17
- Status: Accepted, the members after the founder registered by [0075](0075-a-member-is-registered-by-an-administrator-of-the-account.md)
- Implemented by: `packages/core/features/ouvrir-un-compte.feature`, `Accounts\Application\{OpenAccount,RegisterTheFounder,PublishAccountOpened}`, `Accounts\Contract\Event\AccountOpened`, `PersonalData\Application\CollectTheFounderProfile`, `apps/api/src/SignIn/Command/OpenAccountCommand.php`, the `console` target of the `Makefile`

## Context

An installation on AWS starts empty: locally the accounts come from the demonstration, on AWS nothing makes the first one, and nobody can sign in. Opening an account touches three places: the account and its first member (Accounts), the profile of that member (PersonalData), what they sign in with (authentication, outside the core).

## Decision

- The use case `OpenAccount` opens an account with its founder: `Account::openBy()` refuses an organization without a corporate name and records `AccountOpened`, identities only. The founder becomes its first member with every role (`RegisterTheFounder`, in Accounts); the integration event `AccountOpened` lets PersonalData collect the profile of the founder, empty (`CollectTheFounderProfile`): no personal data crosses the contexts.
- The client fills in the profile as the founder through the use cases that exist, then registers the credentials: `bin/console app:account:open <corporate name> <login email> [--form] [--password]`, the email of the profile being the login email, the password asked without echo.
- On an instance on AWS the command runs from the machine of an administrator, on its tables and user pool, with the temporary credentials of its AWS profile: `make console INSTANCE=<instance> CMD="..."`.

## Consequences

- The first account of an installation is a command away; the members open theirs the same way until a screen of the SPA uses the same use case.
- The roles of the founder are all of them; a member invited later gets fewer.

## Alternatives considered

- A command of the api assembling the aggregates, as the demonstration does: a business rule out of the core, to be written again for a screen.
- The command in a Lambda of the console runtime: not interactive, the password would go through the request and its logs.

## Revisit when

The members open their account from the SPA, or invite their colleagues.
