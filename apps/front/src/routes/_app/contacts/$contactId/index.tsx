import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import {
  ContactDetailsSection,
  JobTitleSection,
  NameSection,
} from '@/features/contacts/components/contact-sections'
import { ForgetContact } from '@/features/contacts/components/forget-contact'
import { contactQuery, fullNameOf } from '@/features/contacts/queries'
import { notFoundWhenTheApiSaysSo, uuidParams } from '@/shared/lib/route-params'
import { BackButton } from '@/shared/ui/back-button'
import { Badge } from '@/shared/ui/badge'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

export const Route = createFileRoute('/_app/contacts/$contactId/')({
  params: uuidParams('contactId'),
  loader: ({ context, params }) =>
    context.queryClient.ensureQueryData(contactQuery(params.contactId)).catch(notFoundWhenTheApiSaysSo),
  pendingComponent: () => <PageSkeleton />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: ContactPage,
})

function ContactPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { contactId } = Route.useParams()
  const { data: contact } = useSuspenseQuery(contactQuery(contactId))

  return (
    <div className="space-y-8">
      <BackButton fallback="/contacts" />

      <PageHeader
        title={fullNameOf(contact) || t('contacts.unnamed')}
        description={
          <span className="flex flex-wrap gap-2">
            {contact.jobTitle && <Badge variant="secondary">{contact.jobTitle}</Badge>}
            {contact.masked && <Badge variant="outline">{t('contacts.masked')}</Badge>}
          </span>
        }
        actions={
          contact._links.forget && (
            <ForgetContact
              contact={contact}
              onForgotten={() => void navigate({ to: '/contacts', replace: true })}
            />
          )
        }
      />

      <div className="grid gap-6 lg:grid-cols-2">
        <NameSection contact={contact} />
        <JobTitleSection contact={contact} />
        <ContactDetailsSection contact={contact} />
      </div>
    </div>
  )
}
