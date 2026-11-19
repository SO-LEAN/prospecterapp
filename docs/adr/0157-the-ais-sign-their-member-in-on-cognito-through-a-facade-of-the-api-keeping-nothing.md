# 0157. The AIs sign their member in on Cognito, through a facade of the API keeping nothing

- Date: 2026-11-18
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/src/SignIn/OAuth`, `apps/api/src/SignIn/Controller/{ShowAuthorizationServer,Authorize,ComeBackFromCognito,IssueTokens}Controller.php`, `apps/api/src/SignIn/UserPool.php` (the client `prospecter-mcp`), `apps/api/src/Security/AccessTokenHandler.php` (the firewall `mcp`), `apps/api/infra/cognito.tf`, `apps/api/infra/http-api.tf`, `apps/api/infra/parameters.tf`, `docker/nginx/templates/api.conf.template`, `apps/front/src/shared/auth/session.ts`, `apps/api/tests/Api/SignInOfTheAIsApiTest.php`

## Context
Claude (web, Code) signs a member in on an MCP server by OAuth 2.1: it reads the metadata of the protected resource (RFC 9728) then of its authorization server (RFC 8414), identifies itself by a Client ID Metadata Document (CIMD: its `client_id` an HTTPS URL) when the server advertises it with the authentication `none`, by a dynamic registration (RFC 7591) otherwise, uses the authorization code with PKCE S256, sends `resource` (RFC 8707), refreshes its tokens. The members sign in on Cognito (ADR 0052), and the maintainer decided that an AI is its member signed in on Cognito. Cognito has the authorization code with PKCE, the refresh tokens and a sign-in page of its own (managed login), and lacks the rest: no CIMD nor dynamic registration, no `code_challenge_methods_supported` in its discovery (a client of the revision `2026-07-28` must then stop), no `resource` in its authorization code.

## Decision
- Cognito signs the member in, on its own sign-in page, for a client of the AIs, `prospecter-mcp` (confidential, its secret a secret of the API, read by the API from Cognito), on a domain of the pool: its hosted UI (classic), the tier Lite of the pool having no editor of the managed login.
- The API is the authorization server the AIs see, a facade over Cognito outside `/api/` (`src/SignIn/OAuth`): the two metadata documents (CIMD advertised with `none`, no registration); `/oauth/authorize` checks the client by its CIMD (its `redirect_uri` among those it declares, a loopback address matched without its port), requires PKCE S256, then sends the browser to Cognito with a PKCE of its own; `/oauth/callback` sends it back to the AI; `/oauth/token` checks the verifier of the AI and exchanges the code of Cognito, answering the tokens of Cognito as they are, and passes a refresh to Cognito.
- Nothing kept: the request travels sealed (`sodium_crypto_secretbox`, a key among the secrets of the API) in the `state` given to Cognito, then in the code given to the AI; Cognito refusing its code twice, the code of the facade serves once. No table, no item, nothing in the core: signing in stays outside the hexagon (ADR 0011).
- The rate of these public routes (the metadata, `/oauth/authorize`, `/oauth/callback`, `/oauth/token`) is limited as that of the other public routes of the API: by address by the edge of the installation, and by the throttling of the HTTP API on routes of their own, which the ticket building the facade adds (the stage of `apps/api/infra` throttles nothing today, and throttling its `$default` route would cap the whole API).
- The protocol imposes two exceptions to one intent per endpoint (ADR 0025): `/oauth/token` carries two by its `grant_type`, `/oauth/authorize` and `/oauth/callback` only redirect.

## Consequences
- Nothing to configure in Claude: the URL of `/mcp` is enough, Claude web and Claude Code presenting their published documents.
- The sign-in of an AI is the page of Cognito, not the screen of the SPA: its look is that of the hosted UI of Cognito.
- The tokens of an AI are those of a member. A global sign-out revokes the refresh tokens of every client of the member, their AIs included: the SPA signs out by revoking its own refresh token only (`RevokeToken`), a global sign-out kept for an explicit « sign out everywhere » in the menu of the account. An access token stays valid until it expires (1 h), as for the SPA: the spike asked an access revoked to be refused at the next request, which only keys of Prospecter's own would give.
- Proven locally on the Cognito of MiniStack, its sign-in page, PKCE, the refresh and the revocations included (`tests/Api/SignInOfTheAIsApiTest`); Claude web and Claude Code accepting it need an instance reachable from Anthropic, walked there.
- No key pasted in a header: Cognito issues none that lasts, Claude Code signs in by OAuth like Claude web.
- The facade fetches a URL a client gives (its CIMD): HTTPS only, a host resolving to no loopback, private or link-local address, a short time and a small size, no redirect followed; a test doubles it (no outside service in a test).

## Alternatives considered
- Cognito alone as the authorization server, weighed by the spike: each member typing the id of a client in Claude, Claude Code on a fixed port, and the clients of the revision `2026-07-28` refusing a discovery without `code_challenge_methods_supported`. Refused.
- Prospecter its own authorization server, an access of an AI in Accounts and its keys hashed in DynamoDB, proposed by the first plan of the spike (it gave a revocation at the next request and keys pasted in a header): refused by the maintainer, an AI being its member signed in on Cognito.
- `league/oauth2-server` and `omouren/mcp-oauth-bundle`, weighed for that first plan: the second needs Doctrine and a login page of the server; moot once Cognito issues the tokens.

## Revisit when
Cognito publishes CIMD or `code_challenge_methods_supported`, the facade then reduced to the metadata of the resource; or a member must revoke one AI at once.
