# 0011. Authentication is infrastructure, outside the hexagon

- Date: 2026-10-15
- Status: Accepted
- Implemented by: 80403af; `apps/api/src/Auth`, `docs/auth.md`

## Context

The core had `login` and `refreshUser` use cases and stored the password (md5 with a salt) in its `User` entity. The user considered that authentication is infrastructure, not a use case, and that keeping it in the hexagon would prevent reusing the use cases (through a command bus, with another authorization server); the hexagon also carried a secret it has no business with.

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

- Keep `login` as a use case: only relevant with business rules on signing in (lockout, password policy decided by the business), which do not exist.
- Take out only `login` and `refreshUser`, keeping the password fields in the core `User` with the hashing in the API: the hexagon would still carry a secret.
- Keep the password change in the account use case, with a hashing port: credentials management is infrastructure.

## Revisit when

Signing in gets business rules, or authentication moves to a dedicated server (0012).
