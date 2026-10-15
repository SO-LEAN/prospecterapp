import { UserManager, type UserManagerSettings, WebStorageStateStore } from 'oidc-client-ts'
import { env } from '@/shared/config/env'

/**
 * OAuth 2 authorization code flow with PKCE against the API (public client, no secret).
 * The API is a plain OAuth 2 server, not OpenID Connect: its endpoints are given explicitly.
 * Tokens live in sessionStorage (per tab, gone when the tab closes); the access token
 * is renewed with the refresh token before it expires.
 */
const settings: UserManagerSettings = {
  authority: env.apiUrl,
  metadata: {
    issuer: env.apiUrl,
    authorization_endpoint: `${env.apiUrl}/authorize`,
    token_endpoint: `${env.apiUrl}/token`,
    end_session_endpoint: `${env.apiUrl}/logout`,
  },
  client_id: env.oauthClientId,
  redirect_uri: `${env.appUrl}/auth/callback`,
  post_logout_redirect_uri: env.appUrl,
  response_type: 'code',
  scope: 'api',
  loadUserInfo: false,
  automaticSilentRenew: true,
  userStore: new WebStorageStateStore({ store: window.sessionStorage }),
}

export const userManager = new UserManager(settings)

/** Where to go back after signing in. */
export type SigninState = { returnTo?: string }
