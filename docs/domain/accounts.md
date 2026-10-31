# Accounts

An organization using Prospecter, and the members working in it. Code: `packages/core/src/Accounts`.

## Language

- **Account** (`Account`): an organization using Prospecter, with its legal identity, its logo and its address, the start of the routes to the organizations it prospects. It is managed by its members.
- **Member** (`Member`): a person working in an account, under a profile of their own in PersonalData (a `PersonId`), with their roles. Signing in, with a login email, is the business of authentication, outside the core (ADR 0011, 0052).
- **Roles** (`Role`): the right to sign in (`ROLE_USER`, every member has it), to prospect (`ROLE_PROSPECTOR`), to manage the personal data (`ROLE_PERSONAL_DATA`), to administer the account (`ROLE_ADMIN`, holding all the others).
- **Founder**: the member who opens an account.

## Rules

- An account is opened by its founder, from the console until the members open theirs: the founder is registered with every role and gets an empty profile of their own (`OpenAccount`, `RegisterTheFounder`; ADR 0064).
- A member is registered by an administrator of the account, under a profile of their own, with at least one role among prospector, personal data and administrator; every member signs in (`RegisterAMember`, `Member::registerBy()`; ADR 0075). The right to register is decided by the aggregate.
- A member may change everything of their account, nothing of another one: the account of another is unknown to them (`AccountPossibilities`; ADR 0088).
- A member reads and manages the personal data with the right to, or as an administrator (`PersonalDataRight`; ADR 0031).

## What it publishes

- The service `ConsultMembersService` (who the members are: their account, their profile, their right to the personal data), asked by Prospecting and PersonalData (ADR 0089).
- The integration events `AccountOpened` and `MemberRegistered`, on which PersonalData collects the profile of the founder or of the member (ADR 0046).

## Features

`ouvrir-un-compte`, `inscrire-un-membre`, `consulter-mon-compte`, `reviser-l-identite-de-mon-organisation`, `changer-le-logo-de-mon-organisation`, `demenager-mon-organisation`, and the questions of `langage-publie`.

## In the code

- `src/Accounts/{Domain,Application}`: an organization using Prospecter (`Account`) and its members (`Member`: account, person id, `Role`; signing in, with a login email, belongs to authentication outside the core); the account intents, among them `RelocateMyOrganization` (its address, the start of the routes to the organizations it prospects); `OpenAccount` opens an account with its founder, registered with every role by `RegisterTheFounder` and announced to the other contexts by the integration event `AccountOpened` (ADR 0064); `RegisterAMember` registers a member in the account of the administrator who acts (`Member::registerBy()`, the right to register decided by the aggregate), with at least one role among prospector, personal data and administrator, announced by `MemberRegistered` (ADR 0075).
