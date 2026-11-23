import { useQueryClient } from '@tanstack/react-query'
import { useNavigate } from '@tanstack/react-router'
import { LogOut } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { session } from '@/shared/auth/session'
import { Button } from '@/shared/ui/button'

/** Where a member signs out. They sign out of the app alone and keep their AIs signed in, or sign out everywhere, their AIs included. */
type SignOutScope = 'thisApp' | 'everywhere'

const signOutOf: Record<SignOutScope, () => Promise<void>> = {
  thisApp: () => session.signOut(),
  everywhere: () => session.signOutEverywhere(),
}

/** Signs the member out, forgets what the tab read, and opens the sign-in screen. */
export function useSignOut(scope: SignOutScope = 'thisApp') {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  return async () => {
    await signOutOf[scope]()
    queryClient.clear()
    await navigate({ to: '/auth/sign-in', search: { returnTo: '/' } })
  }
}

/** Signs out from a page outside the shell, which has no user menu. */
export function SignOutButton() {
  const { t } = useTranslation()
  const signOut = useSignOut()

  return (
    <Button variant="outline" onClick={() => void signOut()}>
      <LogOut aria-hidden /> {t('nav.signOut')}
    </Button>
  )
}
