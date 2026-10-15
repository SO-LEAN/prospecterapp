import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import {
  ContactDetailsSection,
  MyOrganizationIdentitySection,
  MyOrganizationLogoSection,
  NameSection,
  PasswordSection,
  PictureSection,
  UserNameSection,
} from '@/features/account/components/account-sections'
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
    <div className="space-y-8">
      <PageHeader title={t('account.title')} description={t('account.subtitle')} />
      <div className="grid gap-6 lg:grid-cols-2">
        <div className="space-y-6">
          <PictureSection account={account} />
          <NameSection account={account} />
          <ContactDetailsSection account={account} />
        </div>
        <div className="space-y-6">
          <UserNameSection account={account} />
          <PasswordSection account={account} />
          <MyOrganizationIdentitySection account={account} />
          <MyOrganizationLogoSection account={account} />
        </div>
      </div>
    </div>
  )
}
