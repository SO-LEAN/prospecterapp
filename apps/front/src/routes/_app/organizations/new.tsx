import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { StartProspectingForm } from '@/features/organizations/components/start-prospecting-form'
import { useStartProspecting } from '@/features/organizations/mutations'
import { TagsField } from '@/features/tags/components/tags-field'
import { useTagTheNewOrganization } from '@/features/tags/mutations'
import { useGoBack } from '@/shared/hooks/use-go-back'
import { PageHeader } from '@/shared/ui/page-header'

export const Route = createFileRoute('/_app/organizations/new')({
  component: NewOrganization,
})

/** A toast reports a tag refused after the organization was created. The organization's page opens anyway. */
const keepingTheOrganization = () => undefined

function NewOrganization() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const cancel = useGoBack(() => navigate({ to: '/organizations', replace: true }))
  const start = useStartProspecting()
  const tagTheNewOrganization = useTagTheNewOrganization()
  const [tags, setTags] = useState<string[]>([])
  const showInsteadOfTheForm = (organizationId: string) =>
    navigate({ to: '/organizations/$organizationId', params: { organizationId }, replace: true })

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <PageHeader title={t('organizations.start.title')} />
      <StartProspectingForm
        onCancel={cancel}
        tags={<TagsField value={tags} onChange={setTags} />}
        onSubmit={async (body) => {
          const organization = await start.mutateAsync({ body })
          await tagTheNewOrganization(organization.id, tags).catch(keepingTheOrganization)
          await showInsteadOfTheForm(organization.id)
        }}
      />
    </div>
  )
}
