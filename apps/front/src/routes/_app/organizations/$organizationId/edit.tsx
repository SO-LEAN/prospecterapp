import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { OrganizationForm } from '@/features/organizations/components/organization-form'
import { useReplaceOrganization } from '@/features/organizations/mutations'
import { organizationQuery } from '@/features/organizations/queries'
import { organizationIdParams } from '@/features/organizations/route-params'
import { toFormValues } from '@/features/organizations/schema'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

export const Route = createFileRoute('/_app/organizations/$organizationId/edit')({
  params: organizationIdParams,
  loader: ({ context, params }) =>
    context.queryClient.ensureQueryData(organizationQuery(params.organizationId)),
  pendingComponent: () => <PageSkeleton />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: EditOrganization,
})

function EditOrganization() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { organizationId } = Route.useParams()
  const { data: organization } = useSuspenseQuery(organizationQuery(organizationId))
  const replace = useReplaceOrganization()
  const back = () => navigate({ to: '/organizations/$organizationId', params: { organizationId } })

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <PageHeader
        title={t('organizations.edit.title', {
          name: organization.corporateName ?? organization.email ?? '',
        })}
      />
      <OrganizationForm
        defaultValues={toFormValues(organization)}
        submitLabel={t('common.save')}
        onCancel={() => void back()}
        onSubmit={async (input) => {
          await replace.mutateAsync({
            params: { path: { id: String(organizationId) } },
            body: { ...input, holdBy: organization.holdBy ?? null },
          })
          await back()
        }}
      />
    </div>
  )
}
