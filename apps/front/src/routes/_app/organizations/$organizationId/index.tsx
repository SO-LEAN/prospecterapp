import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute } from '@tanstack/react-router'
import { AddressSection } from '@/features/organizations/components/address-section'
import { ContactDetailsSection } from '@/features/organizations/components/contact-details-section'
import { ContactsSection } from '@/features/organizations/components/contacts-section'
import { HoldingSection } from '@/features/organizations/components/holding-section'
import { LegalIdentitySection } from '@/features/organizations/components/legal-identity-section'
import { LogoSection } from '@/features/organizations/components/logo-section'
import { ObservationsSection } from '@/features/organizations/components/observations-section'
import { organizationQuery } from '@/features/organizations/queries'
import { notFoundWhenTheApiSaysSo, uuidParams } from '@/shared/lib/route-params'
import { BackButton } from '@/shared/ui/back-button'
import { Badge } from '@/shared/ui/badge'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

export const Route = createFileRoute('/_app/organizations/$organizationId/')({
  params: uuidParams('organizationId'),
  loader: ({ context, params }) =>
    context.queryClient
      .ensureQueryData(organizationQuery(params.organizationId))
      .catch(notFoundWhenTheApiSaysSo),
  pendingComponent: () => <PageSkeleton />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: OrganizationPage,
})

function OrganizationPage() {
  const { organizationId } = Route.useParams()
  const { data: organization } = useSuspenseQuery(organizationQuery(organizationId))
  const { form, type } = organization.legalIdentity

  return (
    <div className="space-y-8">
      <BackButton fallback="/organizations" />

      <PageHeader
        title={organization.name || organization.contactDetails.email || ''}
        description={
          <span className="flex flex-wrap gap-2">
            {form && <Badge variant="secondary">{form}</Badge>}
            {type && <Badge variant="outline">{type}</Badge>}
          </span>
        }
      />

      <div className="grid gap-6 lg:grid-cols-5">
        <div className="space-y-6 lg:col-span-2">
          <LegalIdentitySection organization={organization} />
          <ContactDetailsSection organization={organization} />
          <ContactsSection organization={organization} />
          <HoldingSection organization={organization} />
          <ObservationsSection organization={organization} />
        </div>
        <div className="space-y-6 lg:col-span-3">
          <LogoSection organization={organization} />
          <AddressSection organization={organization} />
        </div>
      </div>
    </div>
  )
}
