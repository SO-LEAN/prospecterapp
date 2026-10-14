import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { OrganizationForm } from '@/features/organizations/components/organization-form'
import { useCreateOrganization } from '@/features/organizations/mutations'
import { toFormValues } from '@/features/organizations/schema'
import { useGoBack } from '@/shared/hooks/use-go-back'
import { PageHeader } from '@/shared/ui/page-header'

export const Route = createFileRoute('/_app/organizations/new')({
  component: NewOrganization,
})

function NewOrganization() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const cancel = useGoBack(() => navigate({ to: '/organizations', replace: true }))
  const create = useCreateOrganization()

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <PageHeader title={t('organizations.create.title')} />
      <OrganizationForm
        defaultValues={toFormValues()}
        submitLabel={t('organizations.create.submit')}
        onCancel={cancel}
        onSubmit={async (body) => {
          const organization = await create.mutateAsync({ body })
          await navigate({
            to: '/organizations/$organizationId',
            params: { organizationId: organization.id },
            // the filled form is done with: going back from the new organization leads to where it was created from
            replace: true,
          })
        }}
      />
    </div>
  )
}
