import { useQuery, useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, Outlet, redirect, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { AppShell } from '@/app/layout/app-shell'
import { CardPage } from '@/app/layout/card-page'
import { SignOutButton } from '@/app/layout/sign-out'
import { CompleteMyProfileForm } from '@/features/account/components/complete-my-profile-form'
import { MyProfileBeingPrepared } from '@/features/account/components/my-profile-being-prepared'
import { hasAProfileToComplete, isMyProfileBeingPrepared } from '@/features/account/me'
import type { Account } from '@/features/account/mutations'
import { accountQuery, signInQuery } from '@/features/account/queries'
import { ensureSharedQueryData } from '@/shared/api/ensure-shared-query-data'
import { session } from '@/shared/auth/session'
import { PageError } from '@/shared/ui/feedback/page-error'

/** No account yet while the profile of the member is being prepared: the page waits for it. */
const noAccountWhileMyProfileIsPrepared = (error: unknown) => {
  if (isMyProfileBeingPrepared(error)) {
    return undefined
  }
  throw error
}

/**
 * Every page below requires a signed-in member: anonymous visitors sign in, then come back. A member whose profile
 * is being prepared waits for it, then, as one whose profile has no name, completes it first.
 */
export const Route = createFileRoute('/_app')({
  beforeLoad: async ({ location }) => {
    if (!(await session.accessToken())) {
      throw redirect({ to: '/auth/sign-in', search: { returnTo: location.href } })
    }
  },
  loader: async ({ context: { queryClient } }) => {
    const account = await ensureSharedQueryData(queryClient, accountQuery()).catch(
      noAccountWhileMyProfileIsPrepared,
    )
    if (account && hasAProfileToComplete(account)) {
      await queryClient.ensureQueryData(signInQuery())
    }
  },
  component: SignedIn,
  errorComponent: ({ error }) => (
    <PageError error={error}>
      <SignOutButton />
    </PageError>
  ),
})

function SignedIn() {
  const { data: account, error } = useQuery({ ...accountQuery(), retryOnMount: false })

  if (!account) {
    if (error && !isMyProfileBeingPrepared(error)) {
      throw error
    }
    return (
      <MyProfileBeingPrepared>
        <SignOutButton />
      </MyProfileBeingPrepared>
    )
  }

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
