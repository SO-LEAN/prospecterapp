# 0130. The SPA signs in on Cognito with Amplify v6

- Date: 2026-11-16
- Type: architecture
- Status: Accepted
- Supersedes: for the library the SPA signs in with (`amazon-cognito-identity-js`), [0052](0052-cognito-signs-the-members-in.md)
- Implemented by: `apps/front/src/shared/auth/session.ts` and its test, `apps/front/package.json`, `apps/front/vite.config.ts`, `docs/architecture/auth.md`, `docs/architecture/front.md`

## Context

`amazon-cognito-identity-js`, the library 0052 chose for the SPA, is the Cognito library of Amplify v5: in maintenance only, every declaration of it marked deprecated, the only deprecations of the front. It read the Node `global`, which Vite defined for it. The members must keep signing in by SRP from the screen of the SPA, their tokens in the `sessionStorage` of the tab, renewed by the refresh token, signed out everywhere, and reach the Cognito of MiniStack through nginx locally.

## Decision

- The SPA signs in with `aws-amplify` v6, its `auth` category, behind the same `Session` of `shared/auth/session.ts`: `signIn` by `USER_SRP_AUTH` (the session of the tab ended first), `fetchAuthSession` for the access token (renewed once expired, renewal forced after a 401 of the API), `signOut({ global: true })`.
- Amplify is configured once with the pool `GET /api/user-pool` names, its `endpoint` as `userPoolEndpoint` when the API gives one (MiniStack), and keeps its tokens in the `sessionStorage` of Amplify (`cognitoUserPoolsTokenProvider.setKeyValueStorage`).
- A refusal of Cognito keeps its code (the `name` of the error of Amplify); a new password required is the refusal `NewPasswordRequired`, as before. Vite no longer defines `global`.

Verified on the MiniStack of the stack (1.5.27), through nginx, without any workaround: the SRP exchange, the tokens in `sessionStorage` only (under the same keys as before), a reload keeping the session, an expired access token renewed by `GetTokensFromRefreshToken` and accepted by the API, the global sign-out clearing the tab, a wrong password refused with the same message.

## Consequences

- No deprecated library left in the front; Amplify v6 is maintained.
- The chunk of the session grows from 95 kB (28 kB gzip) to 132 kB (38 kB gzip); the main chunk does not change.
- The renewal calls `GetTokensFromRefreshToken` instead of `InitiateAuth REFRESH_TOKEN_AUTH`: nginx passes it as any call of Cognito, and the client already allows the refresh.

## Alternatives considered

An SRP of our own on `@aws-sdk/client-cognito-identity-provider`, named in the ticket as the fallback should Amplify v6 not keep the tokens in `sessionStorage` or not reach the endpoint of MiniStack: it did both, and the protocol of SRP stays in a maintained library.

## Revisit when

Amplify drops `userPoolEndpoint` or the `sessionStorage` of its token provider, or MiniStack stops answering one of the calls it makes.
