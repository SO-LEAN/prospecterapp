import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, Link } from '@tanstack/react-router'
import { zodValidator } from '@tanstack/zod-adapter'
import { Megaphone, Plus } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { CampaignList } from '@/features/campaigns/components/campaign-list'
import { campaignsQuery } from '@/features/campaigns/queries'
import { CAMPAIGN_FILTERS, type CampaignFilter, shownBy } from '@/features/campaigns/status-filter'
import { Button } from '@/shared/ui/button'
import { EmptyState } from '@/shared/ui/feedback/empty-state'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/shared/ui/tabs'

const searchSchema = z.object({
  status: z.enum(CAMPAIGN_FILTERS).catch('running').default('running'),
})

/** The campaigns of my team, read whole (ADR 0144), shown by status, those running by default, and the way to prepare one. */
export const Route = createFileRoute('/_app/campaigns/')({
  validateSearch: zodValidator(searchSchema),
  loader: ({ context }) => context.queryClient.ensureQueryData(campaignsQuery()),
  pendingComponent: () => <PageSkeleton rows={4} />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: Campaigns,
})

function Campaigns() {
  const { t } = useTranslation()
  const { data: list } = useSuspenseQuery(campaignsQuery())
  const { status } = Route.useSearch()
  const navigate = Route.useNavigate()
  const shown = list.items.filter((campaign) => shownBy(status, campaign))

  return (
    <div className="space-y-6">
      <PageHeader
        title={t('campaigns.title')}
        description={t('campaigns.subtitle')}
        actions={
          <Button asChild>
            <Link to="/campaigns/new">
              <Plus aria-hidden />
              {t('campaigns.prepare')}
            </Link>
          </Button>
        }
      />
      {list.items.length === 0 ? (
        <EmptyState
          icon={Megaphone}
          title={t('campaigns.empty.title')}
          description={t('campaigns.empty.description')}
        />
      ) : (
        <div className="space-y-4">
          <Tabs
            value={status}
            onValueChange={(chosen) =>
              navigate({ search: { status: chosen as CampaignFilter }, replace: true })
            }
          >
            <TabsList aria-label={t('campaigns.filter.label')}>
              {CAMPAIGN_FILTERS.map((filter) => (
                <TabsTrigger key={filter} value={filter}>
                  {t(`campaigns.filter.${filter}`)}
                </TabsTrigger>
              ))}
            </TabsList>
          </Tabs>
          {shown.length === 0 ? (
            <p className="text-muted-foreground text-sm">{t(`campaigns.filter.none.${status}`)}</p>
          ) : (
            <CampaignList campaigns={shown} />
          )}
        </div>
      )}
    </div>
  )
}
