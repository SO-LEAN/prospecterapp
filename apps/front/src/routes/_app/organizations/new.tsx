import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { StartProspectingForm } from '@/features/organizations/components/start-prospecting-form'
import { useStartProspecting } from '@/features/organizations/mutations'
import { useGoBack } from '@/shared/hooks/use-go-back'
import { PageHeader } from '@/shared/ui/page-header'

export const Route = createFileRoute('/_app/organizations/new')({
  component: NewOrganization,
})

function NewOrganization() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const cancel = useGoBack(() => navigate({ to: '/organizations', replace: true }))
  const start = useStartProspecting()
  const showInsteadOfTheForm = (organizationId: string) =>
    navigate({ to: '/organizations/$organizationId', params: { organizationId }, replace: true })

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <PageHeader title={t('organizations.start.title')} />
      <StartProspectingForm
        onCancel={cancel}
        onSubmit={async (body) => {
          const organization = await start.mutateAsync({ body })
          await showInsteadOfTheForm(organization.id)
        }}
      />
    </div>
  )
}
