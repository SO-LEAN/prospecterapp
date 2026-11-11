import { api } from '@/shared/api/client'
import { nextReadingOfMyProfile } from './me'

/** The signed-in user's account; shared by the user menu and the account page (one cached request). */
export const accountQuery = () => api.queryOptions('get', '/api/me')

/** What I sign in with, in the user pool. */
export const signInQuery = () => api.queryOptions('get', '/api/me/sign-in')

/**
 * My account as the layout reads it, while my profile may still be being prepared: read again, a delay growing, until
 * it exists or I waited too long; a refusal is read again neither at once, nor by a screen mounted, nor by the window
 * focused, for the wait to last as long as it says.
 */
export const myAccountBeingPreparedQuery = () =>
  api.queryOptions('get', '/api/me', undefined, {
    retry: false,
    retryOnMount: false,
    refetchOnWindowFocus: false,
    refetchInterval: ({ state }) =>
      state.data === undefined && nextReadingOfMyProfile(state.errorUpdateCount),
  })
