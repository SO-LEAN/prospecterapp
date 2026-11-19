# Authentication

The members sign in with a Cognito user pool (ADR 0052); locally MiniStack emulates it (`ministack` service). The SPA (`apps/front`) keeps its own sign-in screen and signs in by SRP: the password never leaves the browser. The API (`apps/api`) is a resource server: it checks the access tokens with the public keys of the pool, and keeps through the admin API of Cognito what a member signs in with.

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

- `prospecter-dev` (`prospecter-test` in the tests), its only client `prospecter-front`: SRP and refresh for the SPA, `ADMIN_USER_PASSWORD_AUTH` for the tests of the API alone (it needs the AWS credentials). Password policy: 8 characters, nothing else.
- A member is a user named by its member id, signing in with the email of its alias (`AliasAttributes: email`): the `username` of the access token is the member id. The login email is an access identifier, distinct from the contact email of the profile kept by PersonalData (ADR 0032).
- `App\SignIn\UserPool` finds the pool and its client by name (their ids are made by Cognito, then cached) and recreates them (`bin/console app:user-pool:recreate [--demo]`, run by `make db`); `--demo` registers `prospector@solean-it.io`, `assistant@solean-it.io` and `newcomer@solean-it.io` with the password `password` under the member ids of `Demo\DemoData` (`Demo\DemoCredentials`). `bin/console app:user:set-password <login email>` sets a password by hand.
- Configuration (`apps/api/.env`): `COGNITO_ENDPOINT` (MiniStack for the API; empty on AWS), `COGNITO_USER_POOL_NAME`, `COGNITO_KEYS_URL` (the JWKS, `%s` the id of the pool), `COGNITO_PUBLIC_ENDPOINT` (where the SPA reaches Cognito; empty on AWS), and the `AWS_*` credentials (dev values for MiniStack).

## Resource server: `apps/api`

- Firewall `api` (`^/api/`, stateless) with Symfony's `access_token` authenticator: `Security\AccessTokenHandler` checks the JWT without calling Cognito: RS256 signature with the key its `kid` names (`SignIn\SigningKeys`, the JWKS read once and cached, read again for an unknown key; `SignIn\RsaPublicKey` turns its modulus and exponent into a PEM), issuer `https://cognito-idp.<region>.amazonaws.com/<pool id>` (the same on MiniStack), `client_id` of the SPA, `token_use=access` (an id token is refused), expiry (`Security\Expires` and `LooseValidAt`: a Cognito token has no "not before"). `Security\MemberProvider` reads the member named by the `username`, its identities and roles, with the query `ConsultMyAccount` (ADR 0040). Missing or invalid token: 401 `application/problem+json` with `WWW-Authenticate: Bearer`.
- `GET /api/user-pool` is public (firewall `user_pool`): the SPA asks it before anyone is signed in. So is `GET /api/booking-pages/{code}` (firewall `booking_page`, whatever the code: one cut short or not drawn by the API leads to no page, as an unknown one): the page a public link of a booking leads to, read by whomever holds the link, answering who its host is and their free slots, or nothing at all for a link offering none, limited by the code of the link (ADR 0151, 0153).
- `POST /api/accounts/{account}/gmail-notifications` has a firewall of its own (`gmail_notifications`, ADR 0154): Pub/Sub pushes there the notifications of Gmail with a token Google signed for the account of service of the subscription of the account; `Connectors\Google\GmailPushAuthenticator` checks it without calling Google, but for its keys (`Connectors\Google\GoogleSigningKeys`, `GOOGLE_CERTS_URL`, cached as those of Cognito): RS256 with the key its `kid` names, issuer `https://accounts.google.com` or `accounts.google.com`, audience the endpoint of the account (`DEFAULT_URI` followed by its path, `Connectors\GmailNotificationEndpoint`), `email` the account of service the topic of the account names (`ConsultMailboxPusher`) and verified, expiry, on the clock of the controllers; anything else, an account without topic too, is a 401, which Pub/Sub retries until its retention ends. The pusher holds `ROLE_GMAIL_PUSHER`, no member.
- `GET /api/me/sign-in`, `PATCH /api/me/sign-in/login-email` (422 when another member signs in with it), `PATCH /api/me/sign-in/password` (204): what I sign in with (`SignIn\Credentials`, admin calls of Cognito). The API checks that a login email is free before writing it (`ListUsers`), Cognito refusing a second holder of an alias on AWS as a safety net (`AliasExistsException`); MiniStack does not, the check of the API does.
- A token stays valid until it expires even after signing out: the price of not calling Cognito on every request.
- Roles are checked by `#[IsGranted]` (403 problem+json from `Security\ApiAccessDeniedHandler`, its `reason` `roleNotAllowed`), the voters whether this member may reach a resource of Prospecting, the aggregate whether its state allows the intention (ADR 0081, 0088). The core knows no password.

## The AIs (decided, to come)

An AI reaches the MCP server of the API as the member it signed in on Cognito, with exactly their rights (ADR 0156, 0157, `mcp.md`):

- a firewall `mcp` (`^/mcp$`, stateless) checks the access tokens of Cognito as the firewall `api` does: `/mcp` takes those of `prospecter-front` and of a second client of the pool, `prospecter-mcp` (confidential, its secret a secret of the API), `/api/` those of `prospecter-front` only; without a valid token, 401 with `WWW-Authenticate: Bearer resource_metadata="<api>/.well-known/oauth-protected-resource/mcp"`;
- the member signs in on the sign-in page of Cognito (managed login, on a domain of the pool), never on the screen of the SPA; the API is the authorization server the AIs see, a facade over Cognito keeping nothing (`src/SignIn/OAuth`: the metadata of RFC 9728 and RFC 8414, `/oauth/authorize`, `/oauth/callback`, `/oauth/token`, the request sealed in the redirects);
- every sign-out of the SPA being a global sign-out today, it would revoke the refresh tokens of the AIs of the member too: the ticket building the facade has the SPA revoke its own refresh token only (`RevokeToken`), a global sign-out kept for « sign out everywhere »; an access token stays valid until it expires, as for the SPA.

## SPA side

- `src/shared/auth/session.ts` (`aws-amplify` v6, its `auth` category, ADR 0130): `signIn` (SRP, the session of the tab ended first; a refusal of Cognito given as its code, a new password required as `NewPasswordRequired`, another step Cognito asks for, a password to reset or a code, refused by the name of that step, the form saying the sign-in failed), `signOut` (global sign-out, then the tokens of the tab forgotten), `accessToken` (renewed with the refresh token once expired), `renewedAccessToken` (renewal forced). The pool comes from `GET /api/user-pool`: Amplify is configured once with it, its `endpoint` as `userPoolEndpoint` when the API names one (MiniStack), and its tokens kept by `cognitoUserPoolsTokenProvider` in the `sessionStorage` of Amplify.
- The tokens are kept in `sessionStorage`: per tab, gone when the tab closes, readable by scripts of the page. Keep the Content Security Policy strict in production.
- `routes/_app.tsx` guards every page: without an access token, to `/auth/sign-in?returnTo=<the page>`. `routes/auth/sign-in.tsx` (`features/sign-in`) signs in, then goes back to `returnTo` (a path of the SPA only, `shared/auth/sign-in-page.ts`). The form tells a wrong email or password (`NotAuthorizedException`, `UserNotFoundException`) from too many attempts.
- `shared/api/client.ts`: `api` adds the access token, and on a 401 renews it once and sends the request again, or sends the member to the sign-in screen.
- Locally the SPA, served in https, reaches MiniStack through nginx: `https://api.prospecter.localhost/cognito/` passes the calls of Cognito only (`X-Amz-Target: AWSCognitoIdentityProviderService.*` and the preflights), never DynamoDB or S3 (`docker/nginx/templates/api.conf.template`).

## Tests

- API: `ApiTestCase` recreates the test pool for each test with the members of the demonstration, and `login()` signs in for real (`ADMIN_USER_PASSWORD_AUTH`); `tests/Api/AccessTokenTest` (no token, expired by moving the clock `app.token_clock`, another client, an id token, a forged signature, a user naming no member), `tests/Api/SignInTest` (the pool for the SPA, the links, login email changed then signed in with, unique, password changed then signed in with, too short), `tests/SignIn/RsaPublicKeyTest`.
- Front: `features/sign-in/components/sign-in-form.test.tsx`; `e2e/journey.spec.ts` signs in through the real screen and SRP and signs out; `e2e/sign-in-screen.spec.ts` checks a wrong password.

## In the code of the API

- Firewall `api` (`^/api/`, stateless, `access_token` with `Security\AccessTokenHandler`): the JWT is checked with the published key its `kid` names (`SignIn\SigningKeys`, cached), its issuer, its client, `token_use=access` and its expiry (`app.token_clock`); its `username` is the member id, loaded by `Security\MemberProvider` into `App\Security\User`, received as `#[CurrentUser] User $user`; its ids go into every command and query; the voters decide the access to a resource of Prospecting (ADR 0081), PersonalData and Accounts the rest (ADR 0026, 0075).
- `src/SignIn`: the user pool known by name (`UserPool`, ids cached, `COGNITO_*` variables), what a member signs in with (`Credentials`: login email unique on the platform, password, through the admin calls of Cognito), `GET /api/user-pool` (public, where the SPA signs in) and `/api/me/sign-in`; `app:user-pool:recreate [--demo]`, `app:user:set-password`, `app:account:open` (an account and its founder, ADR 0064), `app:member:register` (a member in the account of an administrator, ADR 0075), on AWS through `make console`.
- The members of the demonstration sign in with `Demo\DemoCredentials` under the member ids `DemoData::PROSPECTOR` and `DemoData::ASSISTANT`.
