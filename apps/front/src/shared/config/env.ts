/**
 * The configuration of the SPA: what an installation sets in /config.js (written by its Terraform, loaded by
 * index.html before the app, ADR 0066), else the Vite `VITE_*` variables of the build (see .env).
 */
declare global {
  interface Window {
    prospecter?: { apiUrl?: string }
  }
}

export const env = {
  apiUrl: window.prospecter?.apiUrl ?? (import.meta.env.VITE_API_URL as string),
} as const
