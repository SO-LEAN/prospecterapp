# Authentication

This document states the rules in force. The decision records (`docs/adr`) explain why: read them only for that, or before changing a rule (ADR 0090).

Members sign in with a Cognito user pool (ADR 0052). Locally, MiniStack emulates it (the `ministack` service). The SPA (`apps/front`) keeps its own sign-in screen and signs in with SRP, so the password never leaves the browser. The API (`apps/api`) is a resource server: it checks access tokens against the pool's public keys, and it manages a member's sign-in credentials through the Cognito admin API.

```
SPA (prospecter.localhost)                                   API (api.prospecter.localhost, apps/api)
 1. GET /api/user-pool ─────────────────────────────────────▶  {region, userPoolId, clientId, endpoint}
                                                                (the pool and its client found by name)
                                                            Cognito (MiniStack, through /cognito/ locally)
 2. InitiateAuth USER_SRP_AUTH (email, SRP_A) ──────────────▶  PASSWORD_VERIFIER challenge (salt, SRP_B…)
 3. RespondToAuthChallenge (proof of the password) ─────────▶  access token (1 h, username = member id),
                                                                id token, refresh token
                                                            API
 4. GET /api/me  Authorization: Bearer <access token> ──────▶  signature (JWKS key of the kid), issuer,
                                                                client, token_use, expiry; member loaded by id
 5. expired, or 401: GetTokensFromRefreshToken ────────────▶  (Cognito) a new access token, the request sent again
```

## User pool

- The pool is `prospecter-dev` (`prospecter-test` in the tests). It has two clients:
  - `prospecter-front`: SRP and refresh for the SPA, plus `ADMIN_USER_PASSWORD_AUTH` for the API tests alone (it needs the AWS credentials);
  - `prospecter-mcp`, for the AIs (ADR 0157): confidential, authorization code with PKCE on the pool's sign-in page, callback `<api>/oauth/callback`, refresh.

  Password policy: 8 characters, nothing else.
- A member is a Cognito user named by their member id, who signs in with their email alias (`AliasAttributes: email`). The access token's `username` is therefore the member id. The login email is an access identifier, distinct from the contact email of the profile that PersonalData keeps (ADR 0032).
- `App\SignIn\UserPool` finds the pool and its clients by name (Cognito generates their ids and the `prospecter-mcp` secret, which are then cached) and recreates them (`bin/console app:user-pool:recreate [--demo]`, run by `make db`). `--demo` registers `prospector@solean-it.io`, `assistant@solean-it.io` and `newcomer@solean-it.io` with the password `password`, under the member ids of `Demo\DemoData` (`Demo\DemoCredentials`). `bin/console app:user:set-password <login email>` sets a password by hand.
- Configuration (`apps/api/.env`):
  - `COGNITO_ENDPOINT`: MiniStack for the API; empty on AWS;
  - `COGNITO_USER_POOL_NAME`;
  - `COGNITO_KEYS_URL`: the JWKS, `%s` standing for the pool id;
  - `COGNITO_PUBLIC_ENDPOINT`: where the SPA reaches Cognito; empty on AWS;
  - `COGNITO_SIGN_IN_URL` and `COGNITO_OAUTH_URL`: the sign-in page where the browser of an AI's member is sent, and the token endpoint the API calls (the pool's domain on AWS, MiniStack locally);
  - `OAUTH_SEAL_KEY`: the key that seals the AIs' requests (`mcp.md`);
  - the `AWS_*` credentials (dev values for MiniStack).

## Resource server: `apps/api`

- The `api` firewall (`^/api/`, stateless) uses Symfony's `access_token` authenticator. `Security\AccessTokenHandler` checks the JWT without calling Cognito:
  - RS256 signature with the key its `kid` names (`SignIn\SigningKeys` reads the JWKS once and caches it, and reads it again for an unknown key; `SignIn\RsaPublicKey` turns the key's modulus and exponent into a PEM);
  - issuer `https://cognito-idp.<region>.amazonaws.com/<pool id>` (the same on MiniStack);
  - `client_id` of the SPA;
  - `token_use=access` (an id token is refused);
  - expiry (`Security\Expires` and `LooseValidAt`: a Cognito token has no "not before").

  `Security\MemberProvider` loads the member named by the `username`, with their identities and roles, through the `ConsultMyAccount` query (ADR 0040). A missing or invalid token gets a 401 `application/problem+json` with `WWW-Authenticate: Bearer`.
- `GET /api/user-pool` is public (firewall `user_pool`): the SPA calls it before anyone signs in. Two booking endpoints are public too:
  - `GET /api/booking-pages/{code}` (firewall `booking_page`, whatever the code: a truncated code, or one the API did not generate, leads to no page, like an unknown one). It is the page a public booking link leads to. Anyone holding the link can read it. It says who the host is and lists their free slots, or returns nothing for a link that offers none. It is rate limited by the link's code (ADR 0151, 0153).
  - `POST /api/booking-pages/{code}/appointments`, behind the same firewall: anyone holding the link books a slot, also rate limited by its code (ADR 0164).
- `POST /api/accounts/{account}/gmail-notifications` has its own firewall (`gmail_notifications`, ADR 0154). Pub/Sub pushes Gmail notifications there, with a token Google signed for the service account of the account's subscription. `Connectors\Google\GmailPushAuthenticator` checks it without calling Google, except to fetch its keys (`Connectors\Google\GoogleSigningKeys`, `GOOGLE_CERTS_URL`, cached like Cognito's):
  - RS256 with the key its `kid` names;
  - issuer `https://accounts.google.com` or `accounts.google.com`;
  - audience: the account's endpoint (`DEFAULT_URI` followed by its path, `Connectors\GmailNotificationEndpoint`);
  - `email`: the service account that the account's topic names (`ConsultMailboxPusher`), verified;
  - expiry, on the controllers' clock.

  Anything else, including an account without a topic, gets a 401, which Pub/Sub retries until its retention ends. The pusher holds `ROLE_GMAIL_PUSHER` and is no member.
- `GET /api/me/sign-in`, `PATCH /api/me/sign-in/login-email` (422 when another member already signs in with that email) and `PATCH /api/me/sign-in/password` (204) manage my sign-in credentials (`SignIn\Credentials`, through Cognito admin calls). The API checks that a login email is free before writing it (`ListUsers`). On AWS, Cognito also refuses a second holder of an alias as a safety net (`AliasExistsException`); MiniStack does not, so the API's check is what protects it locally.
- A token stays valid until it expires, even after the member signs out: that is the price of not calling Cognito on every request.
- `#[IsGranted]` checks the roles (403 problem+json from `Security\ApiAccessDeniedHandler`, with the `reason` `roleNotAllowed`). The voters check whether this member may reach a Prospecting resource, and the aggregate checks whether its state allows the intent (ADR 0081, 0088). The core knows no password.

## The AIs

An AI reaches the API's MCP server as the member who signed it in on Cognito, with exactly that member's rights (ADR 0156, 0157, `mcp.md`):

- The `mcp` firewall (`^/mcp$`, stateless) checks Cognito access tokens the way the `api` firewall does, with `Security\AccessTokenHandler` (`app.mcp_token_handler`). `/mcp` accepts tokens from `prospecter-front` and `prospecter-mcp`; `/api/` accepts only those from `prospecter-front` (`Security\IssuedForOneOf`). Without a valid token, it answers 401 with `WWW-Authenticate: Bearer resource_metadata="<api>/.well-known/oauth-protected-resource/mcp"`, and adds `error="invalid_token"` for a refused token (`Security\McpAuthenticationEntryPoint`, `tests/Api/McpApiTest`). `SignIn\Controller\ShowProtectedResourceController` serves the metadata it points to.

- The member signs in on Cognito's sign-in page (its hosted UI, on a pool domain; MiniStack locally), never on the SPA's screen. To the AIs, the API is the authorization server: a facade over Cognito that stores nothing (`src/SignIn/OAuth`: the RFC 8414 metadata, `/oauth/authorize`, `/oauth/callback`, `/oauth/token`, the request sealed inside the redirects, `mcp.md`).
- Signing out of the SPA revokes only its own refresh token (`RevokeToken`), so the member's AIs stay signed in. « Se déconnecter partout » (in the account menu) is the global sign-out: it also revokes the refresh tokens of the member's AIs. As for the SPA, an access token stays valid until it expires.

## SPA side

- `src/shared/auth/session.ts` (`aws-amplify` v6, its `auth` category, ADR 0130):
  - `signIn`: SRP, after ending the tab's previous session. A Cognito refusal is returned as its code, a required new password as `NewPasswordRequired`. Any other step Cognito asks for (a password to reset, a code) is refused by that step's name, and the form says the sign-in failed.
  - `signOut`: revokes its own refresh token (`RevokeToken`), then forgets the tab's tokens.
  - `signOutEverywhere`: global sign-out, which also revokes the refresh tokens of the member's AIs (ADR 0157).
  - `accessToken`: renewed with the refresh token once expired.
  - `renewedAccessToken`: forces a renewal.

  The pool comes from `GET /api/user-pool`. Amplify is configured once with it, using its `endpoint` as `userPoolEndpoint` when the API names one (MiniStack). `cognitoUserPoolsTokenProvider` keeps the tokens in Amplify's `sessionStorage`.
- The tokens live in `sessionStorage`: per tab, gone when the tab closes, and readable by the page's scripts. Keep the Content Security Policy strict in production.
- `routes/_app.tsx` guards every page: without an access token, it redirects to `/auth/sign-in?returnTo=<the page>`. `routes/auth/sign-in.tsx` (`features/sign-in`) signs in, then goes back to `returnTo` (only a path of the SPA, `shared/auth/sign-in-page.ts`). The form tells a wrong email or password (`NotAuthorizedException`, `UserNotFoundException`) apart from too many attempts.
- `shared/api/client.ts`: `api` adds the access token. On a 401, it renews the token once and sends the request again, or sends the member to the sign-in screen.
- Locally, the SPA is served over HTTPS and reaches MiniStack through nginx: `https://api.prospecter.localhost/cognito/` passes only Cognito calls (`X-Amz-Target: AWSCognitoIdentityProviderService.*` and the preflights), never DynamoDB or S3 (`docker/nginx/templates/api.conf.template`).

## Tests

- API:
  - `ApiTestCase` starts each test from the test pool with the demo members. They are recreated once per test process and restored after each test (`Base/MembersOfTheDemonstration`, ADR 0180). `login()` really signs in (`ADMIN_USER_PASSWORD_AUTH`).
  - `tests/Api/AccessTokenTest`: no token, a token expired by moving the `app.token_clock` clock, another client, an id token, a forged signature, a user who names no member.
  - `tests/Api/SignInTest`: the pool for the SPA, the links, a login email changed then used to sign in, its uniqueness, a password changed then used to sign in, a password too short.
  - `tests/SignIn/RsaPublicKeyTest`.
  - `tests/Api/SignInOfTheAIsApiTest`: an AI signed in through the facade on MiniStack's sign-in page (`mcp.md`).
- Front:
  - `features/sign-in/components/sign-in-form.test.tsx`;
  - `shared/auth/session.test.ts`: signing out of the app alone, signing out everywhere;
  - `app/signed-in.test.tsx`: « Se déconnecter partout » from the account menu;
  - `e2e/journey.spec.ts` signs in through the real screen and SRP, then signs out;
  - `e2e/sign-in-screen.spec.ts` checks a wrong password.

## In the API code

- The `api` firewall (`^/api/`, stateless, `access_token` with `Security\AccessTokenHandler`) checks the JWT against the published key its `kid` names (`SignIn\SigningKeys`, cached), and checks its issuer, its client, `token_use=access` and its expiry (`app.token_clock`). Its `username` is the member id: `Security\MemberProvider` loads it into `App\Security\User`, which a controller receives as `#[CurrentUser] User $user`. Its ids go into every command and query. The voters decide access to a Prospecting resource (ADR 0081); PersonalData and Accounts decide the rest (ADR 0026, 0075).
- `src/SignIn` holds:
  - the user pool, known by name (`UserPool`, ids cached, `COGNITO_*` variables);
  - a member's sign-in credentials (`Credentials`: a login email unique across the platform, a password, through Cognito admin calls);
  - `GET /api/user-pool` (public, where the SPA signs in) and `/api/me/sign-in`;
  - the commands `app:user-pool:recreate [--demo]`, `app:user:set-password`, `app:account:open` (an account and its founder, ADR 0064) and `app:member:register` (a member in an administrator's account, ADR 0075), run on AWS through `make console`.
- The demo members sign in with `Demo\DemoCredentials`, under the member ids `DemoData::PROSPECTOR` and `DemoData::ASSISTANT`.
