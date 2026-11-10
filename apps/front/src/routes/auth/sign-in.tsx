import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { CardPage } from '@/app/layout/card-page'
import { SignInForm } from '@/features/sign-in/components/sign-in-form'
import { useSignIn } from '@/features/sign-in/mutations'
import type { SignInValues } from '@/features/sign-in/schema'
import { safeReturnTo } from '@/shared/auth/sign-in-page'

/**
 * The sign-in screen of the SPA (ADR 0037, 0052): an anonymous visitor is sent here with the page it asked
 * for, and goes back to it once signed in.
 */
export const Route = createFileRoute('/auth/sign-in')({
  validateSearch: (search: Record<string, unknown>) => ({ returnTo: safeReturnTo(search.returnTo) }),
  component: SignInPage,
})

function SignInPage() {
  const { t } = useTranslation()
  const signIn = useSignIn()
  const { returnTo } = Route.useSearch()
  const navigate = useNavigate()

  const signInThenGoOn = async (values: SignInValues) => {
    await signIn.mutateAsync(values)
    await navigate({ href: returnTo, replace: true })
  }

  return (
    <CardPage title={t('signIn.title')} description={t('signIn.subtitle')}>
      <SignInForm onSubmit={signInThenGoOn} />
    </CardPage>
  )
}
