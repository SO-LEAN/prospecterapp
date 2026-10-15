# 0030. Bounded contexts Accounts and Prospecting, a shared kernel, code grouped by context

- Date: 2026-10-15
- Status: Accepted
- Supersedes: the point of [0026](0026-roles-in-the-api-ownership-and-state-in-the-aggregate.md) letting an organization be managed by its own users
- Implemented by: `packages/core/src/{Accounts,Prospecting,SharedKernel}`, `apps/api/src/{Accounts,Prospecting,Dashboard,Shared}`, `tests/Architecture/BoundedContextsTest` (core and API)

## Context

`Organization` had two meanings in one class and one table: the organization using Prospecter (its users, its identity, its logo) and an organization it prospects (an owner, a holding, the rule "a corporate name or an email"), told apart by a null owner. Unifying them produced oddities: one's own organization could be changed through the prospecting endpoints and attached to a holding. The code was grouped by layer (`Domain/`, `Application/`). The user saw two bounded contexts emerging and asked to be challenged on them, and to group the code by context, not by layer. A third context, for personal data, was decided at the same time and comes next.

## Decision

- Two bounded contexts in the core, each with its own `Domain` and `Application`:
  - **Accounts**: an `Account` (an organization using Prospecter, managed by its members) and its `Member`s (the former `User`), with the intents under `/api/me`;
  - **Prospecting**: the `Organization`s an account prospects (always owned by an `AccountId`), acted on by a `Prospector`.
- A **shared kernel**, kept small: the published identities `AccountId` and `MemberId`, `Email`, `ContactDetails`, `LegalIdentity`, `File`, `Page`, `Uuid`, the exceptions, `CommandHandler`, the port `Storage` and `FileStore`.
- **Context map**: Accounts upstream, Prospecting downstream. Prospecting never uses Accounts: it reads a `Prospector` (a member id and its account id) through its own port `Prospectors`, implemented in the API by the anticorruption adapter `AccountsProspectors` from the members.
- My own organization is an account, not a prospected organization: `/api/organizations/{its id}` answers 404, it is changed through `/api/me/organization`. An organization is managed only by the prospectors of the account prospecting it.
- The API follows the same folders (`src/Accounts`, `src/Prospecting`, `src/Shared`, and `src/Dashboard` composing both). Tables `accounts`, `members`, `organizations`. A test in the core and one in the API fail when a context uses the other one; the API allows two crossings on purpose: the anticorruption adapter and `UniqueEmails`.

## Consequences

- One word, one meaning per context; the oddities of the unified organization are gone.
- The folders scream the business: `Prospecting/Application/StartProspecting`, `Accounts/Domain/Account`.
- Prospecting depends on a port it owns: Accounts can change, or move to another service, behind `AccountsProspectors`.
- The unique email across accounts, members and prospected organizations still spans the contexts (a rule inherited from the former single table of persons): tolerated until the personal data context takes it over.
- The dashboard composes two reads: the name of my organization from Accounts, the number of prospected organizations from Prospecting.

## Alternatives considered

- Naming the contexts `MyAccount` and `SalesProspecting` (the user's first names): `MyAccount` names the viewpoint of a screen and would not fit an administrator managing other members; "Sales" adds nothing while no other sales context exists. The user chose `Accounts` and `Prospecting`.
- Naming the third context `Identity`: kept for authentication (credentials, token subject, ADR 0012); the user chose `PersonalData`.
- Keeping the code grouped by layer: the user asked for a grouping by context.

## Revisit when

- The personal data context comes (step 2): members' personal data and the unique email move there.
- Prospecting needs more from Accounts than a prospector (an account plan, quotas): widen the port, never import Accounts.
