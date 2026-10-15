# 0010. OAuth 2 authorization code with PKCE for the SPA

- Date: 2026-10-14
- Status: Accepted
- Implemented by: 20f66d6; `docs/auth.md`, `config/packages/league_oauth2_server.yaml`

## Context

The SPA is a public client (it cannot keep a secret) calling an API on another host. The previous session-based sign-in served Twig pages.

## Decision

- The API is an OAuth 2 authorization server and resource server (`league/oauth2-server-bundle`).
- Authorization code with PKCE, as OAuth 2.1 recommends for browser applications; password and implicit grants disabled.
- JWT access tokens for 15 minutes, refresh tokens for 30 days, rotated at each use; consent auto-approved for the first-party client `prospecter-front`.
- Sign-in page served by the API (session firewall `main`), API firewall stateless with bearer tokens, CORS limited to the SPA origin.
- The SPA uses `oidc-client-ts` and keeps tokens in `sessionStorage` (per tab, readable by the page's scripts).

## Consequences

- Standard flow: any OAuth client library works, and the authorization server can move out later (0012).
- Tokens readable by JavaScript: an XSS would expose them; the Content Security Policy must stay strict.
- Keys and secrets to manage per environment (`make keys`, `OAUTH_*` overridden in production).

## Alternatives considered

- Password grant: deprecated, teaches clients to handle passwords.
- Session cookie shared between SPA and API: same-site constraints across hosts, CSRF handling.
- Backend-for-frontend holding the tokens in an HttpOnly cookie: safer, more infrastructure.
- Tokens in memory only: sign in again on every reload.

## Revisit when

Authentication moves to a dedicated server (0012), or the security policy requires tokens out of reach of scripts (BFF).
