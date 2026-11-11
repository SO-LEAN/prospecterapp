import { useQueryClient } from '@tanstack/react-query'
import { useNavigate } from '@tanstack/react-router'
import { LogOut } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { session } from '@/shared/auth/session'
import { Button } from '@/shared/ui/button'

/** Signs the member out, forgets what the tab read, and opens the sign-in screen. */
export function useSignOut() {
  const navigate = useNavigate()
  const queryClient = useQueryClient()

  return async () => {
    await session.signOut()
    queryClient.clear()
    await navigate({ to: '/auth/sign-in', search: { returnTo: '/' } })
  }
}

/** Signing out from a page outside the shell, where the user menu is not. */
export function SignOutButton() {
  const { t } = useTranslation()
  const signOut = useSignOut()

  return (
    <Button variant="outline" onClick={() => void signOut()}>
      <LogOut aria-hidden /> {t('nav.signOut')}
    </Button>
  )
}
