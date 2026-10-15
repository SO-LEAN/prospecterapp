# 0034. An auth app is the authorization server; the API is a resource server

- Date: 2026-10-15
- Status: Accepted
- Supersedes: [0012](0012-dedicated-authorization-server.md)
- Implemented by: `apps/auth`, `apps/api/src/Security/{AccessTokenHandler,MemberProvider}.php`, `docker/nginx/auth_fastcgi.conf`

## Context

The API was at once the OAuth 2 authorization server (league bundle, sign-in page, credentials) and the resource server. 0012 planned to delegate authentication to a dedicated server. The user found that the API did too many things that were not its own and asked for authentication in an app of its own, Symfony like the others. Members sign in with a login email kept by authentication (0032).

## Decision

- `apps/auth` (Symfony 8.1, its own SQLite database) is the authorization server: OAuth 2 with the league bundle (authorization code with PKCE, rotated refresh tokens), the sign-in page, the credentials (login email, password), and `/auth/me` to read and change them. It knows a member by its id only, not the core.
- The subject of the access tokens is the member id, not the login email: changing the login email no longer invalidates the session.
- The API is a resource server: Symfony's `access_token` authenticator checks the JWT with the public key of the authorization server (signature, expiry, client) without calling it, and loads the member named by the subject. Its tests sign their own tokens with a key pair made at bootstrap.
- Both apps share the host `api.local.prospecter.io`: nginx gives `/authorize`, `/token`, `/login`, `/logout` and `/auth/*` to the auth app.
- The SPA reads and changes what a member signs in with on `/auth/me`, through a typed client generated from the contract of the auth app.

## Consequences

- The API has no password, no session, no OAuth table; a dedicated authorization server can replace `apps/auth` without touching it.
- An access token stays valid until it expires (15 minutes) even when its refresh token is revoked: the API does not ask the authorization server.
- Creating a member is now two steps in two apps (the member in the API, its credentials in the auth); the fixtures share the id of the prospector, nothing creates members at run time yet.
- Two OpenAPI contracts and two typed clients in the front.

## Alternatives considered

- A host of its own (`auth.local.prospecter.io`): the user preferred paths under the API host, nothing to change on the workstations (hosts file, certificate).
- A lighter stack than Symfony: the user chose Symfony for every app.

## Revisit when

- Members are created at run time (sign-up, invitations): the API then has to create the credentials (an internal call or an event).
- The authorization server moves to its own host or to a product (Keycloak, an identity provider).
