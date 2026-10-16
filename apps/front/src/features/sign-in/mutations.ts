import { useMutation } from '@tanstack/react-query'
import { session } from '@/shared/auth/session'
import type { SignInValues } from './schema'

/** The credentials go to the user pool; its refusals (wrong password, too many attempts) are shown by the form. */
export function useSignIn() {
  return useMutation({
    mutationFn: ({ email, password }: SignInValues) => session.signIn(email, password),
    meta: { silentClientErrors: true },
  })
}
