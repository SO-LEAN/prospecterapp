import { useMutation } from '@tanstack/react-query'
import { session } from '@/shared/auth/session'
import type { SignInValues } from './schema'

/** Sends the credentials to the user pool. The form shows its refusals (wrong password, too many attempts). */
export function useSignIn() {
  return useMutation({
    mutationFn: ({ email, password }: SignInValues) => session.signIn(email, password),
    meta: { silentClientErrors: true },
  })
}
