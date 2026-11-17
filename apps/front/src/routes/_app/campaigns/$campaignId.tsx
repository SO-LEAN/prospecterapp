import { useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { CampaignForm } from '@/features/campaigns/components/campaign-form'
import { CampaignMenu } from '@/features/campaigns/components/campaign-menu'
import { CampaignRecipients } from '@/features/campaigns/components/campaign-recipients'
import { CampaignTarget } from '@/features/campaigns/components/campaign-target'
import { FreezeCampaign } from '@/features/campaigns/components/freeze-campaign'
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
 * A campaign: its form at the left, revised whole; at the right, while a draft, the contacts it targets as it is
 * typed, until it is frozen; frozen, its recipients changed by hand, its channel and its tags no longer; and the
 * message saved rendered by the api for the contact hovered (ADR 0144).
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
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { campaignId } = Route.useParams()
  const { data: campaign } = useSuspenseQuery(campaignQuery(campaignId))

  return (
    <div className="space-y-6">
      <BackButton fallback="/campaigns" />
      <PageHeader
        title={campaign.name}
        description={t(`campaigns.status.${campaign.status}`)}
        actions={
          <div className="flex items-center gap-2">
            <FreezeCampaign campaign={campaign} />
            <CampaignMenu
              campaign={campaign}
              onDiscarded={() => navigate({ to: '/campaigns', replace: true })}
            />
          </div>
        }
      />
      {campaign._links.recipients ? (
        <FrozenCampaign key="frozen" campaign={campaign} />
      ) : (
        <DraftCampaign key="draft" campaign={campaign} />
      )}
    </div>
  )
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
