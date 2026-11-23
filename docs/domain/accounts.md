# Accounts

An organization using Prospecter, and the members working in it. Code: `packages/core/src/Accounts`.

## Language

- **Account** (`Account`): an organization using Prospecter. It has a legal identity, a logo and an address; the address is where the routes to the organizations it prospects start. Its members manage it.
- **Member** (`Member`): a person working in an account, with their own profile in PersonalData (a `ProfileId`) and their roles. Signing in with a login email belongs to authentication, outside the core (ADR 0011, 0052).
- **Roles** (`Role`):
  - `ROLE_USER`: the right to sign in; every member has it;
  - `ROLE_PROSPECTOR`: the right to prospect;
  - `ROLE_PERSONAL_DATA`: the right to manage personal data;
  - `ROLE_ADMIN`: the right to administer the account; it holds all the others.
- **Founder**: the member who opens an account.
- **Reminders** (`Reminders`): whether a member is reminded of the exchanges they planned, and how long before (`ReminderDelay`: on time, 5, 10, 15, 30 or 60 minutes). They belong to the member, whatever the device, and the application shows them while it is open (`docs/architecture/front.md`).

## Rules

- The founder opens an account, from the console until members can open their own. The founder is registered with every role and gets their own empty profile (`OpenAccount`, `RegisterTheFounder`; ADR 0064), which they complete at their first sign-in (ADR 0100).
- An administrator of the account registers a member, with their own profile and at least one role among prospector, personal data and administrator. Every member signs in (`RegisterAMember`, `Member::registerBy()`; ADR 0075) and completes their empty profile at their first sign-in (ADR 0100). The aggregate decides who has the right to register.
- A member may change anything in their own account and nothing in another one: other accounts are unknown to them (`AccountPossibilities`; ADR 0088).
- A member reads and manages personal data if they have the right to, or as an administrator (`PersonalDataRight`; ADR 0031).
- An administrator administers their account: they register its members and configure its clients with the providers (`AdministrationRight`; ADR 0125; Connectors keeps the clients themselves).
- A member chooses their own reminders:
  - ten minutes before by default;
  - one of the offered delays, `ReminderDelayNotOffered` otherwise;
  - turned off without losing the chosen delay.

  `ChangeMyReminders` and `Member::changeReminders()` change them; `ConsultMyReminders` reads them.

## What it publishes

- The service `ConsultMembersService`, which Prospecting, PersonalData, Connectors and Booking ask (ADR 0089, 0125, 0127, 0153). It tells who the members are:
  - their account and their profile;
  - their right to personal data;
  - whether they administer their account (`AdministrationRight`);
  - how long before their planned exchanges they are reminded, none when they turned their reminders off;
  - their account's corporate name.
- The service `ListAccountsService` (the accounts' identities, by slice), for a context that counts by account and needs to know which accounts exist (ADR 0099).
- The integration events `AccountOpened` and `MemberRegistered`, on which PersonalData collects the founder's or the member's profile (ADR 0046).

## Features

`ouvrir-un-compte`, `inscrire-un-membre`, `consulter-mon-compte`, `reviser-l-identite-de-mon-organisation`, `changer-le-logo-de-mon-organisation`, `demenager-mon-organisation`, `regler-mes-rappels`, and the questions in `langage-publie`.

## In the code

`src/Accounts/{Domain,Application}`:

- An organization using Prospecter (`Account`) and its members (`Member`: account, person id, `Role`). Signing in with a login email belongs to authentication, outside the core.
- The account intents, among them `RelocateMyOrganization` (its address, where the routes to the organizations it prospects start).
- `OpenAccount` opens an account with its founder. `RegisterTheFounder` registers the founder with every role, the command that `RegisterTheFounderPolicy` sends when an account opens (ADR 0202), and the integration event `AccountOpened` announces the account to the other contexts (ADR 0064).
- `RegisterAMember` registers a member in the acting administrator's account. The member gets at least one role among prospector, personal data and administrator. The use case calls `Member::registerBy()` with the administrator, who stands for the `Gesture`, and a `Newcomer`: the member, their profile and their roles. The aggregate decides who has the right to register (ADR 0193). `MemberRegistered` announces it (ADR 0075).
- `ChangeMyReminders` changes the acting member's reminders (`RemindersChanged`). `ConsultMyReminders` reads them through `MyRemindersRetriever`, with the defaults for a member who never changed them.
