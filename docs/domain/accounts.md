# Accounts

An organization using Prospecter, and the members working in it. Code: `packages/core/src/Accounts`.

## Language

- **Account** (`Account`): an organization using Prospecter, with its legal identity, its logo and its address, the start of the routes to the organizations it prospects. It is managed by its members.
- **Member** (`Member`): a person working in an account, under a profile of their own in PersonalData (a `PersonId`), with their roles. Signing in, with a login email, is the business of authentication, outside the core (ADR 0011, 0052).
- **Roles** (`Role`): the right to sign in (`ROLE_USER`, every member has it), to prospect (`ROLE_PROSPECTOR`), to manage the personal data (`ROLE_PERSONAL_DATA`), to administer the account (`ROLE_ADMIN`, holding all the others).
- **Founder**: the member who opens an account.
- **Reminders** (`Reminders`): whether a member is reminded of the exchanges they planned, and how long before (`ReminderDelay`: on time, 5, 10, 15, 30 or 60 minutes); theirs, whatever the device.

## Rules

- An account is opened by its founder, from the console until the members open theirs: the founder is registered with every role and gets an empty profile of their own (`OpenAccount`, `RegisterTheFounder`; ADR 0064), theirs to complete at their first sign-in (ADR 0100).
- A member is registered by an administrator of the account, under a profile of their own, with at least one role among prospector, personal data and administrator; every member signs in (`RegisterAMember`, `Member::registerBy()`; ADR 0075) and completes their empty profile at their first sign-in (ADR 0100). The right to register is decided by the aggregate.
- A member may change everything of their account, nothing of another one: the account of another is unknown to them (`AccountPossibilities`; ADR 0088).
- A member reads and manages the personal data with the right to, or as an administrator (`PersonalDataRight`; ADR 0031).
- A member chooses their own reminders: ten minutes before by default, one of the delays offered (`ReminderDelayNotOffered` otherwise), turned off without losing the delay chosen (`ChangeMyReminders`, `Member::changeReminders()`, read by `ConsultMyReminders`).

## What it publishes

- The service `ConsultMembersService` (who the members are: their account, their profile, their right to the personal data), asked by Prospecting and PersonalData (ADR 0089).
- The service `ListAccountsService` (the identities of the accounts, by slice), for a context counting by account which accounts there are (ADR 0099).
- The integration events `AccountOpened` and `MemberRegistered`, on which PersonalData collects the profile of the founder or of the member (ADR 0046).

## Features

`ouvrir-un-compte`, `inscrire-un-membre`, `consulter-mon-compte`, `reviser-l-identite-de-mon-organisation`, `changer-le-logo-de-mon-organisation`, `demenager-mon-organisation`, `regler-mes-rappels`, and the questions of `langage-publie`.

## In the code

- `src/Accounts/{Domain,Application}`: an organization using Prospecter (`Account`) and its members (`Member`: account, person id, `Role`; signing in, with a login email, belongs to authentication outside the core); the account intents, among them `RelocateMyOrganization` (its address, the start of the routes to the organizations it prospects); `OpenAccount` opens an account with its founder, registered with every role by `RegisterTheFounder` and announced to the other contexts by the integration event `AccountOpened` (ADR 0064); `RegisterAMember` registers a member in the account of the administrator who acts (`Member::registerBy()`, the right to register decided by the aggregate), with at least one role among prospector, personal data and administrator, announced by `MemberRegistered` (ADR 0075); `ChangeMyReminders` changes the reminders of the member who acts (`RemindersChanged`), `ConsultMyReminders` reads them through `MyRemindersRetriever`, those by default for a member who never changed them.
