import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { CampaignDailyCount } from '@/features/campaigns/components/campaign-daily-count'
import { CampaignEnded } from '@/features/campaigns/components/campaign-ended'
import { CampaignForm } from '@/features/campaigns/components/campaign-form'
import { CampaignMenu } from '@/features/campaigns/components/campaign-menu'
import { CampaignProgress } from '@/features/campaigns/components/campaign-progress'
import { CampaignRecipients } from '@/features/campaigns/components/campaign-recipients'
import { CampaignRun } from '@/features/campaigns/components/campaign-run'
import { CampaignStatusBadge } from '@/features/campaigns/components/campaign-status-badge'
import { CampaignSummary } from '@/features/campaigns/components/campaign-summary'
import { CampaignTarget } from '@/features/campaigns/components/campaign-target'
import { DailyCapSetting } from '@/features/campaigns/components/daily-cap-setting'
import { FreezeCampaign } from '@/features/campaigns/components/freeze-campaign'
import { StartCampaign } from '@/features/campaigns/components/start-campaign'
import { useReviseCampaign } from '@/features/campaigns/mutations'
import { type Campaign, campaignQuery, campaignRecipientsQuery } from '@/features/campaigns/queries'
import { revisionOf } from '@/features/campaigns/schema'
import { useLiveTarget } from '@/features/campaigns/use-live-target'
import { TagsFilter } from '@/features/tags/components/tags-filter'
import { searchOfTags, taggedContactSlicesQuery } from '@/shared/api/tagged-contacts'
import { notFoundWhenTheApiSaysSo, uuidParams } from '@/shared/lib/route-params'
import { BackButton } from '@/shared/ui/back-button'
import { Badge } from '@/shared/ui/badge'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

/**
 * A campaign. Its form is on the left, and the member revises it as a whole. On the right, a draft shows the contacts
 * it targets as the member types, until the draft is frozen. A frozen campaign's recipients change by hand, but its
 * channel and its tags no longer change. Nothing changes in a started campaign: the member runs it recipient after
 * recipient (ADR 0145), and its header shows its progress. A stopped or ended campaign is read-only, and an ended one
 * says so. The API renders the saved message for the hovered contact (ADR 0144).
 */
export const Route = createFileRoute('/_app/campaigns/$campaignId')({
  params: uuidParams('campaignId'),
  loader: async ({ context, params }) => {
    const campaign = await context.queryClient
      .ensureQueryData(campaignQuery(params.campaignId))
      .catch(notFoundWhenTheApiSaysSo)
    if (campaign._links.recipients) {
      void context.queryClient.prefetchQuery(campaignRecipientsQuery(campaign.id))
    } else {
      void context.queryClient.prefetchInfiniteQuery(
        taggedContactSlicesQuery(searchOfTags(campaign.targetedTags)),
      )
    }
  },
  pendingComponent: () => <PageSkeleton rows={6} />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: CampaignPage,
})

function CampaignPage() {
  const navigate = useNavigate()
  const { campaignId } = Route.useParams()
  const { data: campaign } = useSuspenseQuery(campaignQuery(campaignId))

  return (
    <div className="space-y-6">
      <BackButton fallback="/campaigns" />
      <PageHeader
        title={campaign.name}
        description={
          <span className="flex items-center gap-3">
            <CampaignStatusBadge status={campaign.status} />
            {campaign.startedAt && <CampaignProgress campaign={campaign} />}
            {campaign.status === 'started' && <CampaignDailyCount campaign={campaign} />}
          </span>
        }
        actions={
          <div className="flex items-center gap-2">
            <FreezeCampaign campaign={campaign} />
            <StartCampaign campaign={campaign} />
            <CampaignMenu
              campaign={campaign}
              onDiscarded={() => navigate({ to: '/campaigns', replace: true })}
            />
          </div>
        }
      />
      <DailyCapSetting campaign={campaign} />
      <CampaignInItsState campaign={campaign} />
    </div>
  )
}

function CampaignInItsState({ campaign }: { campaign: Campaign }) {
  if (!campaign._links.recipients) {
    return <DraftCampaign key="draft" campaign={campaign} />
  }
  if (!campaign._links.revise) {
    return <StartedCampaign key="started" campaign={campaign} />
  }
  return <FrozenCampaign key="frozen" campaign={campaign} />
}

function DraftCampaign({ campaign }: { campaign: Campaign }) {
  const { t } = useTranslation()
  const target = useLiveTarget(campaign.tags, campaign.channel)
  const revise = useReviseCampaign()
  const [changed, setChanged] = useState(false)

  return (
    <div className="grid gap-6 lg:grid-cols-2">
      <CampaignForm
        values={revisionOf(campaign)}
        submitLabel={t('common.save')}
        tagsField={(chosen, onChange) => <TagsFilter chosen={chosen} onChange={onChange} />}
        onChanged={setChanged}
        onTargetingChange={target.follow}
        onSubmit={(body) => revise.mutateAsync({ params: { path: { id: campaign.id } }, body })}
      />
      <CampaignTarget target={target} campaignId={campaign.id} changed={changed} />
    </div>
  )
}

function FrozenCampaign({ campaign }: { campaign: Campaign }) {
  const { t } = useTranslation()
  const revise = useReviseCampaign()
  const [changed, setChanged] = useState(false)

  return (
    <div className="grid gap-6 lg:grid-cols-2">
      <CampaignForm
        values={revisionOf(campaign)}
        submitLabel={t('common.save')}
        searchFrozen
        tagsField={(chosen) => (
          <ul aria-label={t('campaigns.fields.tags')} className="flex flex-wrap gap-1.5">
            {chosen.map((tag) => (
              <li key={tag}>
                <Badge variant="secondary">{tag}</Badge>
              </li>
            ))}
          </ul>
        )}
        onChanged={setChanged}
        onSubmit={(body) => revise.mutateAsync({ params: { path: { id: campaign.id } }, body })}
      />
      <CampaignRecipients campaign={campaign} changed={changed} />
    </div>
  )
}

function StartedCampaign({ campaign }: { campaign: Campaign }) {
  if (campaign.status === 'started') {
    return <CampaignRun campaign={campaign} />
  }
  return (
    <div className="grid gap-6 lg:grid-cols-2">
      <div className="space-y-4">
        {campaign.status === 'ended' && <CampaignEnded />}
        <CampaignSummary campaign={campaign} />
      </div>
      <CampaignRecipients campaign={campaign} changed={false} />
    </div>
  )
}
