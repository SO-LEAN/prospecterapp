# 0011. Authentication is infrastructure, outside the hexagon

- Date: 2026-10-15
- Status: Accepted
- Implemented by: 80403af; `apps/api/src/Auth`, `docs/auth.md`

## Context

The core had `login` and `refreshUser` use cases and stored the password (md5 with a salt) in its `User` entity. A use case requiring a login cannot be reused from another channel (a worker, a command bus, another authorization server), and the hexagon carried a secret it has no business with.

## Decision

- The core knows no password: `login`, `refreshUser`, `BadCredentialException`, the password, the salt and the hashing are removed; the account use case no longer changes the password.
- Use cases receive an already authenticated `UseCaseConsumer` and keep only the business authorization (roles, ownership, 0019).
- `apps/api/src/Auth` holds the credentials: `Credentials` (table `credentials`, one row per user id), `Passwords` (Symfony's `auto` hasher, outdated hashes upgraded at sign-in), `app:user:set-password`. It knows the user only by id and user name.
- The authenticator and the user provider read the storage directly; `PUT /api/me` sets a new password through `Passwords`.

## Consequences

- md5 is gone; hashing follows Symfony's recommendations.
- Use cases can be called from anything that knows who the user is.
- Existing md5 hashes are not migrated (the development database is recreated or updated, production is not deployed).

## Alternatives considered

- Keep `login` as a use case: makes sense only with business rules on signing in (lockout, password policy decided by the business); there are none. If they come, they would be a domain policy called by the authenticator.
- Keep the password in the core entity and hash it in the API: the hexagon would still carry the secret.

## Revisit when

Signing in gets business rules, or authentication moves to a dedicated server (0012).
