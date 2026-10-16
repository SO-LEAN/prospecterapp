# 0037. The sign-in screen is in the SPA; the authorization server signs in with a JSON POST and its session cookie

- Date: 2026-10-15
- Status: Accepted, the protocol superseded by [0052](0052-cognito-signs-the-members-in.md)
- Implemented by: `apps/front/src/routes/auth/sign-in.tsx`, `apps/front/src/features/sign-in`, `apps/auth/src/Security/SignInAuthenticator.php`, `apps/auth/src/Controller/SignIn.php`

## Context

The authorization server (0034) rendered its own Twig sign-in page, a copy of the design tokens of the SPA kept in sync by hand, outside the front, its translations and its tests. The user chose "the screen in the SPA, Symfony everywhere".

## Decision

- The SPA owns the sign-in screen (`/auth/sign-in`). The authorization code flow with PKCE (0010) is unchanged: an anonymous `/authorize` is kept in the session of the authorization server and redirected to that screen.
- The screen posts the login email and password as JSON to `POST /login` of the authorization server, with the cookie (CORS with credentials). The authorization server signs the member in with its session cookie (HttpOnly, Secure, SameSite Lax, same site as the SPA) and answers `resume`, the `/authorize` it interrupted; the SPA opens it again and gets its code. Opened directly, the screen starts the flow itself.
- Against login CSRF, `POST /login` accepts the origin of the SPA only; the JSON body also forces a CORS preflight. A wrong login email and a wrong password get the same 401. Five failures in a minute, per address and login email, get a 429 (Symfony `login_throttling`).
- The authorization server renders no page any more: Twig, Form and CSRF tokens leave it.

## Consequences

- One design, one language and one test tooling for every screen; the sign-in is tested like the other forms.
- The authorization server and the SPA must stay on the same site (`*.prospecter.io`): the session cookie is Lax, it would not be sent from another site.
- The credentials pass through the code of the SPA; a compromised SPA could read them, as it could read its tokens already.

## Alternatives considered

- The resource owner password grant (the SPA sends the password to `/token` and gets the tokens): rejected, deprecated and removed from OAuth 2.1, and it would bypass the authorization flow.
- Keeping the Twig page of the authorization server: the user wanted the screen in the SPA.

## Revisit when

- The authorization server leaves the site of the SPA, or is replaced by a product with its own sign-in pages (0034).
