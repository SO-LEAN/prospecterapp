# Authentication

The members sign in with a Cognito user pool (ADR 0052); locally MiniStack emulates it (`ministack` service). The SPA (`apps/front`) keeps its own sign-in screen and signs in by SRP: the password never leaves the browser. The API (`apps/api`) is a resource server: it checks the access tokens with the public keys of the pool, and keeps through the admin API of Cognito what a member signs in with.

```
SPA (local.prospecter.io)                                   API (api.local.prospecter.io, apps/api)
 1. GET /api/user-pool ─────────────────────────────────────▶  {region, userPoolId, clientId, endpoint}
                                                                (the pool and its client found by name)
                                                            Cognito (MiniStack, through /cognito/ locally)
 2. InitiateAuth USER_SRP_AUTH (email, SRP_A) ──────────────▶  PASSWORD_VERIFIER challenge (salt, SRP_B…)
 3. RespondToAuthChallenge (proof of the password) ─────────▶  access token (1 h, username = member id),
                                                                id token, refresh token
                                                            API
 4. GET /api/me  Authorization: Bearer <access token> ──────▶  signature (JWKS key of the kid), issuer,
                                                                client, token_use, expiry; member loaded by id
 5. expired, or 401: InitiateAuth REFRESH_TOKEN_AUTH ───────▶  (Cognito) a new access token, the request sent again
```

## User pool

- `prospecter-dev` (`prospecter-test` in the tests), its only client `prospecter-front`: SRP and refresh for the SPA, `ADMIN_USER_PASSWORD_AUTH` for the tests of the API alone (it needs the AWS credentials). Password policy: 8 characters, nothing else.
- A member is a user named by its member id, signing in with the email of its alias (`AliasAttributes: email`): the `username` of the access token is the member id. The login email is an access identifier, distinct from the contact email of the profile kept by PersonalData (ADR 0032).
- `App\SignIn\UserPool` finds the pool and its client by name (their ids are made by Cognito, then cached) and recreates them (`bin/console app:user-pool:recreate [--demo]`, run by `make db`); `--demo` registers `prospector@solean-it.io` and `assistant@solean-it.io` with the password `password` under the member ids of `Demo\DemoData` (`Demo\DemoCredentials`). `bin/console app:user:set-password <login email>` sets a password by hand.
- Configuration (`apps/api/.env`): `COGNITO_ENDPOINT` (MiniStack for the API; empty on AWS), `COGNITO_USER_POOL_NAME`, `COGNITO_KEYS_URL` (the JWKS, `%s` the id of the pool), `COGNITO_PUBLIC_ENDPOINT` (where the SPA reaches Cognito; empty on AWS), and the `AWS_*` credentials (dev values for MiniStack).

## Resource server: `apps/api`

- Firewall `api` (`^/api/`, stateless) with Symfony's `access_token` authenticator: `Security\AccessTokenHandler` checks the JWT without calling Cognito: RS256 signature with the key its `kid` names (`SignIn\SigningKeys`, the JWKS read once and cached, read again for an unknown key; `SignIn\RsaPublicKey` turns its modulus and exponent into a PEM), issuer `https://cognito-idp.<region>.amazonaws.com/<pool id>` (the same on MiniStack), `client_id` of the SPA, `token_use=access` (an id token is refused), expiry (`Security\Expires` and `LooseValidAt`: a Cognito token has no "not before"). `Security\MemberProvider` reads the member named by the `username`, its identities and roles, with the query `ConsultMyAccount` (ADR 0040). Missing or invalid token: 401 `application/problem+json` with `WWW-Authenticate: Bearer`.
- `GET /api/user-pool` is public (firewall `user_pool`): the SPA asks it before anyone is signed in.
- `GET /api/me/sign-in`, `PATCH /api/me/sign-in/login-email` (422 when another member signs in with it), `PATCH /api/me/sign-in/password` (204): what I sign in with (`SignIn\Credentials`, admin calls of Cognito). The API checks that a login email is free before writing it (`ListUsers`), Cognito refusing a second holder of an alias on AWS as a safety net (`AliasExistsException`); MiniStack does not, the check of the API does.
- A token stays valid until it expires even after signing out: the price of not calling Cognito on every request.
- Roles are checked by `#[IsGranted]` (403 problem+json from `Security\ApiAccessDeniedHandler`), the voters whether this member may reach a resource of Prospecting, the aggregate whether its state allows the intention (ADR 0081). The core knows no password.

## SPA side

- `src/shared/auth/session.ts` (`amazon-cognito-identity-js`): `signIn` (SRP), `signOut` (global sign-out, then the tokens of the tab forgotten), `accessToken` (renewed with the refresh token once expired), `renewedAccessToken`. The pool comes from `GET /api/user-pool`. The library reads the Node `global`: `vite.config.ts` defines it as `globalThis`.
- The tokens are kept in `sessionStorage`: per tab, gone when the tab closes, readable by scripts of the page. Keep the Content Security Policy strict in production.
- `routes/_app.tsx` guards every page: without an access token, to `/auth/sign-in?returnTo=<the page>`. `routes/auth/sign-in.tsx` (`features/sign-in`) signs in, then goes back to `returnTo` (a path of the SPA only, `shared/auth/sign-in-page.ts`). The form tells a wrong email or password (`NotAuthorizedException`, `UserNotFoundException`) from too many attempts.
- `shared/api/client.ts`: `api` adds the access token, and on a 401 renews it once and sends the request again, or sends the member to the sign-in screen.
- Locally the SPA, served in https, reaches MiniStack through nginx: `https://api.local.prospecter.io/cognito/` passes the calls of Cognito only (`X-Amz-Target: AWSCognitoIdentityProviderService.*` and the preflights), never DynamoDB or S3 (`docker/nginx/templates/api.conf.template`).

## Tests

- API: `ApiTestCase` recreates the test pool for each test with the members of the demonstration, and `login()` signs in for real (`ADMIN_USER_PASSWORD_AUTH`); `tests/Api/AccessTokenTest` (no token, expired by moving the clock `app.token_clock`, another client, an id token, a forged signature, a user naming no member), `tests/Api/SignInTest` (the pool for the SPA, the links, login email changed then signed in with, unique, password changed then signed in with, too short), `tests/SignIn/RsaPublicKeyTest`.
- Front: `features/sign-in/components/sign-in-form.test.tsx`; `e2e/journey.spec.ts` signs in through the real screen and SRP, signs out, and checks a wrong password.
