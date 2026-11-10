import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, Outlet, redirect, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { AppShell } from '@/app/layout/app-shell'
import { CardPage } from '@/app/layout/card-page'
import { CompleteMyProfileForm } from '@/features/account/components/complete-my-profile-form'
import { hasAProfileToComplete } from '@/features/account/me'
import type { Account } from '@/features/account/mutations'
import { accountQuery, signInQuery } from '@/features/account/queries'
import { ensureSharedQueryData } from '@/shared/api/ensure-shared-query-data'
import { session } from '@/shared/auth/session'

/**
 * Every page below requires a signed-in member: anonymous visitors sign in, then come back. A member whose profile
 * has no name completes it first.
 */
export const Route = createFileRoute('/_app')({
  beforeLoad: async ({ location }) => {
    if (!(await session.accessToken())) {
      throw redirect({ to: '/auth/sign-in', search: { returnTo: location.href } })
    }
  },
  loader: async ({ context: { queryClient } }) => {
    const account = await ensureSharedQueryData(queryClient, accountQuery())
    if (hasAProfileToComplete(account)) {
      await queryClient.ensureQueryData(signInQuery())
    }
  },
  component: SignedIn,
})

function SignedIn() {
  const { data: account } = useSuspenseQuery(accountQuery())

  if (hasAProfileToComplete(account)) {
    return <CompleteMyProfile account={account} />
  }

  return (
    <AppShell>
      <Outlet />
    </AppShell>
  )
}

function CompleteMyProfile({ account }: { account: Account }) {
  const { t } = useTranslation()
  const { data: signIn } = useSuspenseQuery(signInQuery())
  const navigate = useNavigate()

  return (
    <CardPage title={t('completeMyProfile.title')} description={t('completeMyProfile.subtitle')}>
      <CompleteMyProfileForm
        account={account}
        loginEmail={signIn.loginEmail}
        onCompleted={() => void navigate({ to: '/', replace: true })}
      />
    </CardPage>
  )
}
