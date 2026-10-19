import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import {
  ContactDetailsSection,
  JobTitleSection,
  NameSection,
  OrganizationsSection,
} from '@/features/contacts/components/contact-sections'
import { ForgetContact } from '@/features/contacts/components/forget-contact'
import { contactQuery, fullNameOf } from '@/features/contacts/queries'
import { ExchangesSection } from '@/features/exchanges/components/exchanges-section'
import { timelineQuery } from '@/features/exchanges/queries'
import { notFoundWhenTheApiSaysSo, uuidParams } from '@/shared/lib/route-params'
import { BackButton } from '@/shared/ui/back-button'
import { Badge } from '@/shared/ui/badge'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

export const Route = createFileRoute('/_app/contacts/$contactId/')({
  params: uuidParams('contactId'),
  loader: async ({ context, params }) => {
    const contact = await context.queryClient
      .ensureQueryData(contactQuery(params.contactId))
      .catch(notFoundWhenTheApiSaysSo)
    if (contact._links.exchanges) {
      void context.queryClient.prefetchInfiniteQuery(timelineQuery(contact.id))
    }
    return contact
  },
  pendingComponent: () => <PageSkeleton />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: ContactPage,
})

function ContactPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { contactId } = Route.useParams()
  const { data: contact } = useSuspenseQuery(contactQuery(contactId))
  const name = fullNameOf(contact) || t('contacts.unnamed')

  return (
    <div className="space-y-8">
      <BackButton fallback="/contacts" />

      <PageHeader
        title={name}
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
        <OrganizationsSection contact={contact} />
        {contact._links.exchanges && (
          <ExchangesSection contactId={contact.id} contactName={name} className="lg:col-span-2" />
        )}
      </div>
    </div>
  )
}
