import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, Link } from '@tanstack/react-router'
import { ArrowRight } from 'lucide-react'
import { Suspense } from 'react'
import { useTranslation } from 'react-i18next'
import { myFirstName } from '@/features/account/me'
import { accountQuery } from '@/features/account/queries'
import { ContactSummary } from '@/features/contacts/components/contact-summary'
import { ContactsToAttachSection } from '@/features/contacts-to-attach/components/contacts-to-attach-section'
import { DashboardFigures } from '@/features/dashboard/components/dashboard-figures'
import { ORGANIZATIONS_MAP } from '@/features/dashboard/organizations-map'
import { dashboardQuery } from '@/features/dashboard/queries'
import { startOfDay } from '@/features/exchanges/calendar/dates'
import { MyDayCards, MyDaySkeleton } from '@/features/exchanges/my-day/components/my-day-cards'
import { myDayQuery } from '@/features/exchanges/my-day/queries'
import { ExchangesToProcessCard } from '@/features/exchanges/to-process/components/exchanges-to-process-card'
import { exchangesToProcessQuery } from '@/features/exchanges/to-process/queries'
import { OrganizationCard } from '@/features/organizations/components/organization-card'
import { OrganizationPreview } from '@/features/organizations/components/organization-preview'
import { organizationsQuery } from '@/features/organizations/queries'
import { ToDoLine } from '@/features/to-dos/components/to-dos-section'
import { ensureSharedQueryData } from '@/shared/api/ensure-shared-query-data'
import { Button } from '@/shared/ui/button'
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { GeoMap } from '@/shared/ui/map/geo-map'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

const RECENT = 4
const recentQuery = () => organizationsQuery('')

export const Route = createFileRoute('/_app/')({
  loader: ({ context: { queryClient } }) => {
    void queryClient.prefetchQuery(myDayQuery(startOfDay(new Date())))
    void queryClient.prefetchQuery(exchangesToProcessQuery())
    return Promise.all([
      queryClient.ensureQueryData(dashboardQuery()),
      ensureSharedQueryData(queryClient, accountQuery()),
      queryClient.ensureQueryData(recentQuery()),
    ])
  },
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
            imageUrl: organization.logo?.smallUrl,
            ...organization.location,
          },
        ]
      : [],
  )

  return (
    <div className="space-y-8">
      <PageHeader
        title={t('dashboard.greeting', { name: myFirstName(account) })}
        description={t('dashboard.subtitle', { organization: dashboard.organizationName })}
      />

      <Suspense>
        <ExchangesToProcessCard
          contactSummary={(contactId) => <ContactSummary contactId={contactId} />}
          toDoLine={(toDo, now, onTickedOff) => (
            <ToDoLine toDo={toDo} now={now} withTheirContact onTickedOff={onTickedOff} undoable postponable />
          )}
          contactsToAttach={(contacts) => <ContactsToAttachSection contacts={contacts} />}
        />
      </Suspense>

      <DashboardFigures stats={dashboard.stats} />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Suspense fallback={<MyDaySkeleton />}>
          <MyDayCards contactSummary={(contactId) => <ContactSummary contactId={contactId} />} />
        </Suspense>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
        <Card id={ORGANIZATIONS_MAP} className="scroll-mt-6 lg:col-span-3">
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
