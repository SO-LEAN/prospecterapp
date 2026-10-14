import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { AccountForm } from '@/features/account/components/account-form'
import { accountQuery } from '@/features/account/queries'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

export const Route = createFileRoute('/_app/account')({
  loader: ({ context }) => context.queryClient.ensureQueryData(accountQuery()),
  pendingComponent: () => <PageSkeleton />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: AccountPage,
})

function AccountPage() {
  const { t } = useTranslation()
  const { data: account } = useSuspenseQuery(accountQuery())

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <PageHeader title={t('account.title')} />
      <AccountForm account={account} />
    </div>
  )
}
