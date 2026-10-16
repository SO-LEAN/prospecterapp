# 0032. Members sign in with an email kept by authentication

- Date: 2026-10-15
- Status: Accepted, where the login email is kept superseded by [0052](0052-cognito-signs-the-members-in.md)
- Implemented by: `apps/api/src/Auth/{Credentials,LoginEmails}.php`, `apps/api/src/Security/ProspecterUserProvider.php`, `apps/api/src/Me/Controller/ChangeMyLoginEmail.php`

## Context

Members signed in with a user name kept by `Member` (Accounts), changed through `PATCH /api/me/user-name`. The user wanted the email as login, "much easier". The email of a member is personal data kept by PersonalData (0031), which no other context copies; but a login identifier must be found at every sign-in, and authentication is meant to move to a dedicated server (0012).

## Decision

- A member signs in with an email, unique on the whole platform: it tells which member, of which account, signs in.
- Authentication keeps it with the password (`Auth\Credentials`, `Auth\LoginEmails`): an access identifier, not a copy of the profile. The contact email of the profile, in PersonalData, is another thing and may differ.
- `Member` loses its user name; `PATCH /api/me/user-name` becomes `PATCH /api/me/login-email` (422 when another member signs in with the email); the token subject is the login email, and changing it means signing in again.

## Consequences

- Signing in does not depend on PersonalData; a dedicated authorization server will take `Credentials` as is.
- Two emails per member: the login one and the contact one. The anonymization of a person (step 4) must also reach authentication.
- A member without credentials cannot sign in: registering a member assigns its login email, then its password.

## Alternatives considered

- Authentication reading the email from PersonalData at every sign-in: no copy at all, but the future authorization server would depend on PersonalData; the user chose to keep the login email in authentication.

## Revisit when

The dedicated authorization server comes (0012), or a member needs to sign in to several accounts with the same email.
