/** The sign-in screen, to come back to `returnTo` once signed in. */
export const signInPage = (returnTo: string) => `/auth/sign-in?returnTo=${encodeURIComponent(returnTo)}`

/** Where to go once signed in: a path of the SPA only, never another site. */
export const safeReturnTo = (returnTo: unknown) =>
  typeof returnTo === 'string' && returnTo.startsWith('/') && !returnTo.startsWith('//') ? returnTo : '/'
