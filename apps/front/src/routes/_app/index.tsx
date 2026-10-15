import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, Link } from '@tanstack/react-router'
import { ArrowRight, Building2, MapPinned } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { accountQuery } from '@/features/account/queries'
import { StatCard } from '@/features/dashboard/components/stat-card'
import { dashboardQuery } from '@/features/dashboard/queries'
import { OrganizationCard } from '@/features/organizations/components/organization-card'
import { OrganizationPreview } from '@/features/organizations/components/organization-preview'
import { organizationsQuery } from '@/features/organizations/queries'
import { Button } from '@/shared/ui/button'
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { GeoMap } from '@/shared/ui/map/geo-map'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

const RECENT = 4
const recentQuery = () => organizationsQuery({ page: 1, q: '' })

export const Route = createFileRoute('/_app/')({
  loader: ({ context: { queryClient } }) =>
    Promise.all([
      queryClient.ensureQueryData(dashboardQuery()),
      queryClient.ensureQueryData(accountQuery()),
      queryClient.ensureQueryData(recentQuery()),
    ]),
  pendingComponent: () => <PageSkeleton />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: Dashboard,
})

function Dashboard() {
  const { t } = useTranslation()
  const { data: dashboard } = useSuspenseQuery(dashboardQuery())
  const { data: account } = useSuspenseQuery(accountQuery())
  const { data: recent } = useSuspenseQuery(recentQuery())

  const markers = dashboard.markers.flatMap((organization) =>
    organization.location
      ? [
          {
            id: organization.id,
            label: organization.name,
            imageUrl: organization.logo?.thumbnailUrl,
            ...organization.location,
          },
        ]
      : [],
  )

  return (
    <div className="space-y-8">
      <PageHeader
        title={t('dashboard.greeting', { name: account.firstName || account.userName })}
        description={t('dashboard.subtitle', { organization: dashboard.organizationName })}
      />

      <div className="grid gap-4 sm:grid-cols-2">
        <StatCard
          icon={Building2}
          label={t('dashboard.stats.activeOrganizations')}
          value={dashboard.stats.activeOrganizations ?? recent.totalItems}
        />
        <StatCard icon={MapPinned} label={t('dashboard.stats.mapped')} value={markers.length} />
      </div>

      <div className="grid gap-6 lg:grid-cols-5">
        <Card className="lg:col-span-3">
          <CardHeader>
            <CardTitle className="text-base">{t('dashboard.map.title')}</CardTitle>
          </CardHeader>
          <CardContent>
            {markers.length > 0 ? (
              <GeoMap
                className="h-96"
                markers={markers}
                renderPopup={(marker) => (
                  <OrganizationPreview id={marker.id} name={marker.label} logoUrl={marker.imageUrl} />
                )}
              />
            ) : (
              <p className="grid h-96 place-items-center rounded-xl border border-dashed text-muted-foreground text-sm">
                {t('dashboard.map.empty')}
              </p>
            )}
          </CardContent>
        </Card>

        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle className="text-base">{t('dashboard.recent.title')}</CardTitle>
            <CardAction>
              <Button variant="ghost" size="sm" asChild>
                <Link to="/organizations">
                  {t('dashboard.recent.viewAll')} <ArrowRight />
                </Link>
              </Button>
            </CardAction>
          </CardHeader>
          <CardContent className="space-y-3">
            {recent.items.length === 0 && (
              <p className="text-muted-foreground text-sm">{t('dashboard.recent.empty')}</p>
            )}
            {recent.items.slice(0, RECENT).map((organization) => (
              <OrganizationCard key={organization.id} organization={organization} />
            ))}
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
