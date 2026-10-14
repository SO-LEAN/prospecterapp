import { createFileRoute } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { useAuth } from 'react-oidc-context'
import { Button } from '@/shared/ui/button'
import { FullPageMessage } from '@/shared/ui/feedback/full-page-message'

/** OAuth redirect target: react-oidc-context exchanges the code, then onSigninCallback navigates away. */
export const Route = createFileRoute('/auth/callback')({
  component: Callback,
})

function Callback() {
  const auth = useAuth()
  const { t } = useTranslation()

  if (auth.error) {
    return (
      <FullPageMessage title={t('auth.error')} description={auth.error.message}>
        <Button onClick={() => void auth.signinRedirect()}>{t('auth.retry')}</Button>
      </FullPageMessage>
    )
  }

  return <FullPageMessage loading title={t('auth.signingIn')} />
}
