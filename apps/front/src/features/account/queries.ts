import { api, authApi } from '@/shared/api/client'

/** The signed-in user's account; shared by the user menu and the account page (one cached request). */
export const accountQuery = () => api.queryOptions('get', '/api/me')

/** What I sign in with, on the authorization server. */
export const signInQuery = () => authApi.queryOptions('get', '/auth/me')
