/** The sign-in screen. Once signed in, the member comes back to `returnTo`. */
export const signInPage = (returnTo: string) => `/auth/sign-in?returnTo=${encodeURIComponent(returnTo)}`

/** Where to go once signed in: only a path in the SPA, never another site. */
export const safeReturnTo = (returnTo: unknown) =>
  typeof returnTo === 'string' && returnTo.startsWith('/') && !returnTo.startsWith('//') ? returnTo : '/'
