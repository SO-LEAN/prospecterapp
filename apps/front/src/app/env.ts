/** Build-time configuration (Vite `VITE_*` variables, see .env). */
export const env = {
  apiUrl: import.meta.env.VITE_API_URL as string,
  appUrl: import.meta.env.VITE_APP_URL as string,
  oauthClientId: import.meta.env.VITE_OAUTH_CLIENT_ID as string,
} as const
