# 0040. The security of the API reads who calls with ConsultMyAccount

- Date: 2026-10-15
- Status: Accepted
- Supersedes: the authentication query `IdentifyMember` of [0035](0035-plain-requests-and-responses-typed-refusals.md)
- Implemented by: `packages/core/src/Accounts/Application/AccountResponse.php`, `apps/api/src/Security/MemberProvider.php`

## Context

To keep the domain out of the security of the API, 0035 added a query `IdentifyMember` (identities and roles of a member). The user noticed it duplicated `ConsultMyAccount`: same input, overlapping answer, and a use case that existed only for the framework, like the "refresh user" use case of the 2021 core (0011: authentication is no use case).

## Decision

- `ConsultMyAccount` answers who I am (member, account and person ids, roles) with my account; `IdentifyMember` and `MemberResponse` are removed.
- `Security\MemberProvider` builds the `User` of Symfony from it, on every request.

## Consequences

- One query for my account, whoever asks; the roles are now part of it and tested by its scenario.
- Every request of the API reads the account of the caller as well as the member: one more read by key.

## Alternatives considered

- Keeping both queries under names showing their different roles: the user saw no gain.

## Revisit when

- Reading the account on every request costs too much: the API could then cache who calls for the life of the access token.
