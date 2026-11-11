import { api } from '@/shared/api/client'
import { nextReadingOfMyProfile } from './me'

/** The signed-in user's account; shared by the user menu and the account page (one cached request). */
export const accountQuery = () => api.queryOptions('get', '/api/me')

/** What I sign in with, in the user pool. */
export const signInQuery = () => api.queryOptions('get', '/api/me/sign-in')

type Readings = { state: { data: unknown; errorUpdateCount: number } }

/**
 * My account while my profile is being prepared: read again, a delay growing, until it exists or I waited too long;
 * a refusal is not tried again at once, nor read again by a screen mounted.
 */
export const myAccountBeingPreparedQuery = () => ({
  ...accountQuery(),
  retry: false,
  retryOnMount: false,
  refetchInterval: ({ state }: Readings) =>
    state.data === undefined && nextReadingOfMyProfile(state.errorUpdateCount),
})
