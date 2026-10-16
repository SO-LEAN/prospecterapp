# 0052. Cognito signs the members in; the SPA keeps its screen and signs in by SRP; the API checks the tokens

- Date: 2026-10-16
- Status: Accepted
- Supersedes: [0010](0010-oauth-2-authorization-code-with-pkce.md), [0034](0034-authorization-server-app.md), the protocol of [0037](0037-sign-in-screen-in-the-spa.md) (the screen stays in the SPA), where [0032](0032-login-email-kept-by-authentication.md) keeps the login email, the SQLite of the auth app ([0016](0016-sqlite-without-migrations.md))
- Implemented by: `apps/api/src/SignIn`, `apps/api/src/Security/{AccessTokenHandler,Expires}.php`, `apps/api/src/Demo/DemoCredentials.php`, `apps/front/src/shared/auth`, `docker/nginx/templates/api.conf.template`

## Context

The application is to run serverless on AWS (0048), where Cognito signs users in; `apps/auth` was an OAuth 2 server of our own (league bundle, SQLite, key pair, sessions), the last user of Doctrine. The user wants to keep the sign-in screen of the SPA (0037) rather than a page hosted by Cognito. Locally MiniStack emulates Cognito: it signs its tokens (RS256) and publishes their keys (JWKS), runs the SRP exchange and the refresh, but makes the ids of a pool and of a client at random, and neither keeps an email alias unique nor asks for a code when it changes.

## Decision

- A Cognito user pool (`prospecter-dev`, `prospecter-test` in the tests) with one client, the SPA (`prospecter-front`): SRP and refresh, plus the password flow of the admin API for the tests. A member is a user named by its member id, who signs in with the email of its alias: the access token names the member (`username`), no table maps them. Password policy: 8 characters, as before.
- The SPA signs in on Cognito by SRP (`amazon-cognito-identity-js`): the password never leaves the browser. The tokens stay in `sessionStorage`; the access token is renewed with the refresh token once expired or refused by the API. Locally, the SPA being served in https, it reaches MiniStack through nginx (`https://api.local.prospecter.io/cognito/`), which passes the calls of Cognito only.
- The API is a resource server: it checks the signature with the published key the token names (`kid`, read from the JWKS and cached), the issuer, the client, `token_use=access` and the expiry, without calling Cognito; the member is loaded as before (0040).
- The API knows the pool and its client by name and finds their ids (cached); the SPA asks them on `GET /api/user-pool`, public, ids of a pool and of a client being public by nature.
- What a member signs in with moves to the API (`GET /api/me/sign-in`, `PATCH /api/me/sign-in/login-email`, `PATCH /api/me/sign-in/password`), through the admin calls of Cognito: the login email stays an access identifier distinct from the contact email of the profile (0032), unique on the platform, checked by the API before writing, Cognito refusing a second holder of an alias on AWS as a safety net; it is changed without a code, as before.
- `make db` recreates the pool with the members of the demonstration (`app:user-pool:recreate --demo`); `app:user:set-password` moves to the API. `apps/auth` and Doctrine leave the repository.

## Consequences

- The tests of the API sign in for real on the test pool of MiniStack, recreated for each test; an expired token is checked by moving the clock of the tokens.
- A token stays valid until it expires (an hour on Cognito) even after signing out: the price of not calling Cognito on every request, as with 0034.
- The screen tells "too many attempts" from the refusals of Cognito; only AWS is known to throttle failed sign-ins.

## Alternatives considered

- The page hosted by Cognito (authorization code with PKCE, as 0010): the user keeps the screen of the SPA.
- A lax verifier of tokens for development, checking all but the signature: not needed, MiniStack signs its tokens and publishes their keys.

## Revisit when

- The dev instance on AWS shows a difference with MiniStack (codes on email changes, throttling, the uniqueness of aliases).
