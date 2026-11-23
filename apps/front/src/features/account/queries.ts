import { api } from '@/shared/api/client'
import { nextReadingOfMyProfile } from './me'

/** The signed-in member's account. The user menu and the account page share it (one cached request). */
export const accountQuery = () => api.queryOptions('get', '/api/me')

/** What I sign in with, kept in the user pool. */
export const signInQuery = () => api.queryOptions('get', '/api/me/sign-in')

/**
 * My account as the layout reads it, while my profile may still be in preparation. The query reads it again, with a
 * growing delay, until it exists or I have waited too long. After a refusal, it never reads again at once, on a screen's
 * mount or on the window's focus. So the wait lasts as long as it says.
 */
export const myAccountBeingPreparedQuery = () =>
  api.queryOptions('get', '/api/me', undefined, {
    retry: false,
    retryOnMount: false,
    refetchOnWindowFocus: false,
    refetchInterval: ({ state }) =>
      state.data === undefined && nextReadingOfMyProfile(state.errorUpdateCount),
  })
