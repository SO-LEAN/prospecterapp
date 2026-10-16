/** Build-time configuration (Vite `VITE_*` variables, see .env). */
export const env = {
  apiUrl: import.meta.env.VITE_API_URL as string,
} as const
