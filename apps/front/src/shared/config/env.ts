/**
 * The SPA's configuration. An installation sets it in /config.js: its Terraform writes the file, and index.html
 * loads it before the app (ADR 0066). Without it, the build's Vite `VITE_*` variables apply (see .env).
 */
declare global {
  interface Window {
    prospecter?: { apiUrl?: string }
  }
}

export const env = {
  apiUrl: window.prospecter?.apiUrl ?? (import.meta.env.VITE_API_URL as string),
} as const
