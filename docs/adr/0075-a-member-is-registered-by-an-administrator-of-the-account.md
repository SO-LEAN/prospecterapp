# 0075. A member is registered by an administrator of the account, under a profile of their own

- Date: 2026-10-20
- Status: Accepted
- Supersedes: the founder as the only member of [0064](0064-an-account-is-opened-by-its-founder.md); for the registration of a member, the roles checked by the delivery of [0026](0026-roles-in-the-api-ownership-and-state-in-the-aggregate.md)
- Implemented by: `packages/core/features/inscrire-un-membre.feature`, `Accounts\Application\{RegisterAMember,PublishMemberRegistered}`, `Member::registerBy()`, `Accounts\Contract\Event\MemberRegistered`, `PersonalData\Application\CollectTheMemberProfile`, `apps/api/src/SignIn/Command/RegisterMemberCommand.php`

## Context

Since ADR 0064 an installation gets its accounts and their founders from the console, with every role. Nothing registers a second member: a prospector without the right to manage personal data exists only in the demonstration. A member assembled by hand, out of the use cases, ended up under the profile of another member.

## Decision

- The use case `RegisterAMember` registers a member in the account of the administrator who acts, with the roles given and the right to sign in: `Member::registerBy()` refuses a member who is no administrator (`AdministratorsOnly`) and a member without a role among prospector, personal data and administrator (`MemberWithoutRole`). The right to register is decided by the aggregate, not by the delivery (unlike ADR 0026): the console, its only client, has no security.
- The integration event `MemberRegistered` lets PersonalData collect the profile of the new member, empty, under the identity the client gives (`CollectTheMemberProfile`, as `CollectTheFounderProfile`): no member shares the profile of another.
- The client fills in the profile as the new member through the use cases that exist, then registers the credentials: `bin/console app:member:register <login email of an administrator> <login email> [--first-name] [--last-name] [--personal-data] [--admin] [--password]`, a prospector by default; on an instance on AWS through `make console INSTANCE=<instance>`.

## Consequences

- The members of an account other than its founder are a command away, with only the roles they need.
- Removing a member or changing their roles has no use case yet.

## Alternatives considered

None was weighed.

## Revisit when

The members are invited from the SPA, or their roles change after their registration.
