import { signInApi } from '@/shared/api/client'

/** The credentials go to the authorization server; its refusals (wrong password, too many attempts) are shown by the form. */
export function useSignIn() {
  return signInApi.useMutation('post', '/login', { meta: { silentClientErrors: true } })
}
