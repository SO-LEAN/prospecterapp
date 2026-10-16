import { api } from '@/shared/api/client'

/** The signed-in user's account; shared by the user menu and the account page (one cached request). */
export const accountQuery = () => api.queryOptions('get', '/api/me')

/** What I sign in with, in the user pool. */
export const signInQuery = () => api.queryOptions('get', '/api/me/sign-in')
