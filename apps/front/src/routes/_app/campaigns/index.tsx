import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, Link } from '@tanstack/react-router'
import { Megaphone, Plus } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { CampaignList } from '@/features/campaigns/components/campaign-list'
import { campaignsQuery } from '@/features/campaigns/queries'
import { Button } from '@/shared/ui/button'
import { EmptyState } from '@/shared/ui/feedback/empty-state'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

/** The campaigns of my team, read whole (ADR 0144), and the way to prepare one. */
export const Route = createFileRoute('/_app/campaigns/')({
  loader: ({ context }) => context.queryClient.ensureQueryData(campaignsQuery()),
  pendingComponent: () => <PageSkeleton rows={4} />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: Campaigns,
})

function Campaigns() {
  const { t } = useTranslation()
  const { data: list } = useSuspenseQuery(campaignsQuery())

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
        <CampaignList campaigns={list.items} />
      )}
    </div>
  )
}
