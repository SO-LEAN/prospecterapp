# The MCP server

What is in force; the reasons are in the records it names (`docs/adr`), read only to know why or before changing it (ADR 0090).

An AI (Claude web, Claude Code, any client of the Model Context Protocol) reaches Prospecter as the member it signed in, with exactly their rights: the API serves an MCP server without session on `https://api.<domain>/mcp` (ADR 0156), and the AI signs the member in on Cognito through a facade of the API keeping nothing (ADR 0157). Status: the server runs, with one tool, `qui-suis-je`, and the metadata of the protected resource (steps 1, 2 and 7 below); the facade signing the AIs in (steps 3 to 6 and 8) is to come, until then an AI calls with an access token of the SPA given by hand.

```
AI (Claude)                         API (api.<domain>)                               Cognito
 1. POST /mcp, no token ──────────▶ 401 WWW-Authenticate: Bearer resource_metadata=…
 2. GET /.well-known/oauth-protected-resource/mcp ─▶ {resource: <api>/mcp, authorization_servers: [<api>]}
 3. GET /.well-known/oauth-authorization-server ───▶ endpoints, S256, CIMD, auth method none
 4. browser: GET /oauth/authorize ─▶ client checked (its metadata document), PKCE of the AI
                                     302 ───────────────────────────────────────────▶ sign-in page (managed login)
                                                                                      the member signs in
 5.                                  GET /oauth/callback?code=<Cognito>&state=<seal> ◀── 302
                                     302 to the AI: code=<seal of the request and of the code of Cognito>
 6. POST /oauth/token ─────────────▶ seal opened, verifier of the AI checked ─────────▶ code exchanged (client secret, PKCE of the API)
                                     ◀── the tokens of Cognito, as they are ◀──────────
 7. POST /mcp, Bearer <access token of Cognito> ─▶ firewall mcp: the member, their roles; the tool runs
 8. expired: POST /oauth/token grant_type=refresh_token ─▶ passed to Cognito
```

## The server

- `symfony/mcp-bundle` (Symfony AI) on `mcp/sdk`, both experimental, pinned at an exact version and moved by hand, their changelogs read. `session.store: none` came after the last tag of the bundle (`v1.0.0-BETA1`), on its `main` with `mcp/sdk` 0.10.0: the bundle is pinned at that commit (`dev-main#790442a8…` in `apps/api/composer.json`), `mcp/sdk` at `0.10.0`, until a tag carries it.
- One server, `prospecter` (`config/packages/mcp.yaml`), HTTP « streamable » on `/mcp` of the host of the API, `allowed_hosts` that host, in the Lambda of the API behind its HTTP API (`$default` on AWS, nginx locally): no infrastructure of its own.
- Without session: both eras of the protocol on the same endpoint, the SDK routing each request by its `params._meta`. The revision `2026-07-28` has no handshake and no session; the clients of the `initialize` handshake (`2025-11-25` and before) are answered without `Mcp-Session-Id`, each request in a session of its own thrown away after it, `GET` and `DELETE` 405, without the requests of the server (elicitation, sampling), the subscriptions and the logging, which no tool needs.
- Its rate, as that of the public routes of the facade, limited as the other public routes of the API: by address by the edge of the installation, and by the throttling of the HTTP API on routes of their own, added with them (the stage throttles nothing today, and throttling `$default` would cap the whole API).
- No CORS: `/mcp` is called by servers (Claude web) and native clients (Claude Code), never by a page.

## Who calls

- The firewall `mcp` (`^/mcp$`, stateless) checks the access tokens of Cognito as the firewall `api` does (`Security\AccessTokenHandler`, `auth.md`), those of the SPA today, those of the client `prospecter-mcp` beside them once the facade makes it, and loads the same `App\Security\User`: an AI does what its member may, reads the personal data in clear or masked as they do, no scope of its own.
- Without a token: 401 `WWW-Authenticate: Bearer resource_metadata="<api>/.well-known/oauth-protected-resource/mcp"` (the entry point); a token refused (expired, of another pool or client, an id token) adds `error="invalid_token"` (RFC 6750) (the failure handler of the authenticator); both `Security\McpAuthenticationEntryPoint`, naming the metadata by `SignIn\OAuth\ProtectedResource`. Claude starts its sign-in only on a 401, never on a tool answering an error.

## Writing a tool

- One tool per intent, `src/<Context>/Mcp/<Intent>Tool.php` (or a folder composing contexts, as `src/Me`), a service carrying `#[McpTool]` (name in French kebab-case, a description for the AI), its namespace listed in the `registry` of the server (`config/packages/mcp.yaml`).
- The tools: `qui-suis-je` (`Me\Mcp\WhoAmITool`), the member served, as `/api/me` reads them, their account, and whether they read the personal data in clear (`readsPersonalData`, the role `ROLE_PERSONAL_DATA`).
- Its tests: `tests/Api/McpApiTest` calls the tools in each era (a `tools/call` carrying the revision in its `_meta`; `initialize` then `tools/call`, no `Mcp-Session-Id`), with a member of each right, and without or with a refused token; `tests/Api/ProtectedResourceApiTest` the metadata.
- It receives `Security` (injected), reads the member (`App\Security\User`), checks a role by `Security::isGranted()` as a controller declares `#[IsGranted]`, and calls the readers, queries and commands of the core as a controller does; it answers an array the SDK turns into its result.

## The sign-in of the AIs

Cognito is the authority, but cannot be the authorization server Claude expects by itself: no Client ID Metadata Document nor dynamic registration, no `code_challenge_methods_supported` in its discovery, no `resource` (RFC 8707) in its authorization code. The facade of the API fills those gaps and keeps nothing (`src/SignIn/OAuth`):

- `GET /.well-known/oauth-protected-resource/mcp` (and without `/mcp`, RFC 9728; `SignIn\Controller\ShowProtectedResourceController`, public, out of the contract of the API): `resource` `<api>/mcp` exactly as the member types it in Claude, `authorization_servers` the API alone (Claude reads the first only).
- `GET /.well-known/oauth-authorization-server` (RFC 8414): its endpoints, `response_types_supported: [code]`, `grant_types_supported: [authorization_code, refresh_token]`, `code_challenge_methods_supported: [S256]`, `token_endpoint_auth_methods_supported: [none]`, `client_id_metadata_document_supported: true` (with `none`, the condition of Claude to use it), no `registration_endpoint`.
- `GET /oauth/authorize`: the client is its Client ID Metadata Document, an HTTPS URL fetched (its host resolving to no loopback, private or link-local address, 5 s, 5 kB, no redirect followed), its `client_id` equal to its URL, the `redirect_uri` among its `redirect_uris`, a loopback address matched without its port (Claude Code declares `http://localhost/callback` and `http://127.0.0.1/callback`); `code_challenge` S256 required; `resource`, when given, `<api>/mcp`. Then a redirect to the sign-in page of Cognito with the client `prospecter-mcp`, a PKCE of the API and, as `state`, the request sealed. An invalid client or redirect: 400 `application/problem+json`, never a redirect to an address not checked.
- `GET /oauth/callback`: Cognito comes back with its code; a redirect to the AI with its `state` and, as `code`, the request and the code of Cognito sealed (5 minutes).
- `POST /oauth/token` (`application/x-www-form-urlencoded`): `authorization_code`, the seal opened, the verifier of the AI checked against its challenge, the same client and redirect, then the code of Cognito exchanged with the secret of `prospecter-mcp` and the verifier of the API, the tokens of Cognito answered as they are; `refresh_token`, passed to Cognito. Every refusal `invalid_grant` (RFC 6749). The protocol imposes two exceptions to one intent per endpoint (ADR 0025): `/oauth/token` carries two by its `grant_type`, `/oauth/authorize` and `/oauth/callback` only redirect.
- Nothing kept: the request travels sealed (`sodium_crypto_secretbox`, its key a secret of the API), in the `state` given to Cognito then in the code given to the AI; Cognito refusing its code a second time, the code of the facade serves once. No table, no item, nothing in the core.
- Claude web identifies itself by the document Anthropic publishes, its redirect `https://claude.ai/api/mcp/auth_callback`; Claude Code by its own (`https://claude.ai/oauth/claude-code-client-metadata`), on a loopback port of the session. Neither needs anything configured.
- A global sign-out revokes the refresh tokens of every client of the member, their AIs included; every sign-out of the SPA is one today, so the ticket building the facade has the SPA revoke its own refresh token only (`RevokeToken`), a global sign-out kept for « sign out everywhere ». An access token stays valid until it expires (1 h), as for the SPA (`auth.md`).

## Trying it

- The spike #202 proved the server on a throwaway branch, before it was built: in the Lambda on MiniStack, a `tools/call` of the revision `2026-07-28` (its `_meta` carrying `io.modelcontextprotocol/protocolVersion` and `io.modelcontextprotocol/clientCapabilities`, the headers `MCP-Protocol-Version`, `Mcp-Method` and `Mcp-Name`), then `initialize` and `tools/call` of `2025-11-25` answered without `Mcp-Session-Id`, `GET` 405, a token refused 401, and the MCP Inspector.
- The metadata name the API by `APP_API_HOST`, without the port a worktree serves its stack on: a client following them locally needs the stack of the main checkout, on 443.
- Locally: `curl` with a token of a member of the demonstration (`Authorization: Bearer`, the access token the SPA sends, read in the network panel of the browser), in each era (a `tools/call` carrying in `params._meta` the revision `2026-07-28`; `initialize` then `tools/call`), and the MCP Inspector (`npx @modelcontextprotocol/inspector --cli <url> --transport http --header "Authorization: Bearer …" --method tools/list`).
- Not run yet: the facade, Claude Code and Claude web (the server and its 401 only run); the steps below are those the ticket building the facade runs.
- Claude Code: `claude mcp add --transport http prospecter https://api.<domain>/mcp`, then `/mcp` to sign in.
- Claude web: Customize › Connectors › Add custom connector, the URL `https://api.<domain>/mcp`, nothing else: an instance reachable from the egress of Anthropic (`160.79.104.0/21`), which the local stack is not.
