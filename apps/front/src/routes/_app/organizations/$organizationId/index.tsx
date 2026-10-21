import { useQuery, useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { accountQuery } from '@/features/account/queries'
import { OrganizationExchangesSection } from '@/features/exchanges/components/organization-exchanges-section'
import { organizationTimelineQuery } from '@/features/exchanges/queries'
import { AddressSection } from '@/features/organizations/components/address-section'
import { ContactDetailsSection } from '@/features/organizations/components/contact-details-section'
import { ContactsSection } from '@/features/organizations/components/contacts-section'
import { HoldingSection } from '@/features/organizations/components/holding-section'
import { LegalIdentitySection } from '@/features/organizations/components/legal-identity-section'
import { LogoSection } from '@/features/organizations/components/logo-section'
import { ObservationsSection } from '@/features/organizations/components/observations-section'
import { StopProspecting } from '@/features/organizations/components/stop-prospecting'
import {
  type Organization,
  type OrganizationContact,
  organizationContactsQuery,
  organizationQuery,
} from '@/features/organizations/queries'
import { notFoundWhenTheApiSaysSo, uuidParams } from '@/shared/lib/route-params'
import { BackButton } from '@/shared/ui/back-button'
import { Badge } from '@/shared/ui/badge'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

export const Route = createFileRoute('/_app/organizations/$organizationId/')({
  params: uuidParams('organizationId'),
  loader: ({ context, params }) =>
    Promise.all([
      context.queryClient
        .ensureQueryData(organizationQuery(params.organizationId))
        .catch(notFoundWhenTheApiSaysSo)
        .then((organization) => {
          if (organization._links.exchanges) {
            void context.queryClient.prefetchInfiniteQuery(organizationTimelineQuery(organization.id))
          }
          return organization
        }),
      context.queryClient.ensureQueryData(accountQuery()),
    ]),
  pendingComponent: () => <PageSkeleton />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: OrganizationPage,
})

function OrganizationPage() {
  const { organizationId } = Route.useParams()
  const { data: organization } = useSuspenseQuery(organizationQuery(organizationId))
  const { data: account } = useSuspenseQuery(accountQuery())
  const { form, type } = organization.legalIdentity
  const navigate = useNavigate()
  const name = nameOf(organization)

  return (
    <div className="space-y-8">
      <BackButton fallback="/organizations" />

      <PageHeader
        title={name}
        description={
          <span className="flex flex-wrap gap-2">
            {form && <Badge variant="secondary">{form}</Badge>}
            {type && <Badge variant="outline">{type}</Badge>}
          </span>
        }
        actions={
          organization._links.stopProspecting && (
            <StopProspecting
              organization={organization}
              onStopped={() => void navigate({ to: '/organizations', replace: true })}
            />
          )
        }
      />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
        <div className="space-y-6 lg:col-span-2">
          <LegalIdentitySection organization={organization} />
          <ContactDetailsSection organization={organization} />
          <ContactsSection organization={organization} />
          <HoldingSection organization={organization} />
          <ObservationsSection organization={organization} />
        </div>
        <div className="space-y-6 lg:col-span-3">
          <LogoSection organization={organization} />
          <AddressSection organization={organization} start={account.organization.location} />
          {organization._links.exchanges && <Exchanges organization={organization} name={name} />}
        </div>
      </div>
    </div>
  )
}

const nameOf = (organization: Organization) => organization.name || organization.contactDetails.email || ''

const fullNameOf = (contact: OrganizationContact) =>
  [contact.name.firstName, contact.name.lastName].filter(Boolean).join(' ')

/** Its timeline, noted with the organization itself or with one of its contacts. */
function Exchanges({ organization, name }: { organization: Organization; name: string }) {
  const contacts = useQuery(organizationContactsQuery(organization.id))

  return (
    <OrganizationExchangesSection
      organizationId={organization.id}
      interlocutors={{
        organization: name,
        contacts: (contacts.data?.items ?? []).map((contact) => ({
          id: contact.id,
          name: fullNameOf(contact),
        })),
      }}
    />
  )
}
