import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { CollectContactForm } from '@/features/contacts/components/collect-contact-form'
import { useCollectContact } from '@/features/contacts/mutations'
import { useGoBack } from '@/shared/hooks/use-go-back'
import { PageHeader } from '@/shared/ui/page-header'

export const Route = createFileRoute('/_app/contacts/new')({
  component: NewContact,
})

function NewContact() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const cancel = useGoBack(() => navigate({ to: '/contacts', replace: true }))
  const collect = useCollectContact()
  const showInsteadOfTheForm = (contactId: string) =>
    navigate({ to: '/contacts/$contactId', params: { contactId }, replace: true })

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <PageHeader title={t('contacts.collect.title')} />
      <CollectContactForm
        onCancel={cancel}
        onSubmit={async (body) => {
          const contact = await collect.mutateAsync({ body })
          await showInsteadOfTheForm(contact.id)
        }}
      />
    </div>
  )
}
