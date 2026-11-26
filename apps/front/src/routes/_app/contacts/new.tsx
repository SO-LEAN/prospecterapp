import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { CollectContactForm } from '@/features/organizations/components/collect-contact-form'
import { useCollectContact } from '@/features/organizations/mutations'
import { useGoBack } from '@/shared/hooks/use-go-back'
import { PageHeader } from '@/shared/ui/page-header'

/** A new contact from the contacts list. The member chooses their organization in the same form. */
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
      <PageHeader title={t('contacts.new')} />
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
