import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, Link } from '@tanstack/react-router'
import { ArrowLeft, Pencil } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { OrganizationDetails } from '@/features/organizations/components/organization-details'
import { OrganizationLogo } from '@/features/organizations/components/organization-logo'
import { organizationQuery } from '@/features/organizations/queries'
import { organizationIdParams } from '@/features/organizations/route-params'
import { Badge } from '@/shared/ui/badge'
import { Button } from '@/shared/ui/button'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

export const Route = createFileRoute('/_app/organizations/$organizationId/')({
  params: organizationIdParams,
  loader: ({ context, params }) =>
    context.queryClient.ensureQueryData(organizationQuery(params.organizationId)),
  pendingComponent: () => <PageSkeleton />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: OrganizationPage,
})

function OrganizationPage() {
  const { t } = useTranslation()
  const { organizationId } = Route.useParams()
  const { data: organization } = useSuspenseQuery(organizationQuery(organizationId))

  return (
    <div className="space-y-8">
      <Button variant="ghost" size="sm" className="-ml-2 text-muted-foreground" asChild>
        <Link to="/organizations">
          <ArrowLeft /> {t('organizations.title')}
        </Link>
      </Button>

      <PageHeader
        title={organization.corporateName ?? organization.email}
        description={
          <span className="flex flex-wrap gap-2">
            {organization.form && <Badge variant="secondary">{organization.form}</Badge>}
            {organization.type && <Badge variant="outline">{organization.type}</Badge>}
          </span>
        }
        actions={
          <Button asChild>
            <Link to="/organizations/$organizationId/edit" params={{ organizationId }}>
              <Pencil /> {t('common.edit')}
            </Link>
          </Button>
        }
      />

      <OrganizationLogo organization={organization} />
      <OrganizationDetails organization={organization} />
    </div>
  )
}
