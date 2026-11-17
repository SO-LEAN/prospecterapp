import { useSuspenseInfiniteQuery, useSuspenseQuery } from '@tanstack/react-query'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { CampaignForm } from '@/features/campaigns/components/campaign-form'
import { CampaignMenu } from '@/features/campaigns/components/campaign-menu'
import { CampaignTarget } from '@/features/campaigns/components/campaign-target'
import { useReviseCampaign } from '@/features/campaigns/mutations'
import { campaignQuery } from '@/features/campaigns/queries'
import { revisionOf } from '@/features/campaigns/schema'
import { taggedContactSlicesQuery } from '@/features/contacts/queries'
import { searchOfTags } from '@/features/tags/chosen-tags'
import { TagsFilter } from '@/features/tags/components/tags-filter'
import { notFoundWhenTheApiSaysSo, uuidParams } from '@/shared/lib/route-params'
import { BackButton } from '@/shared/ui/back-button'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

/**
 * A draft: its form at the left, revised whole; at the right the contacts it targets now, the search by its targeted
 * tags as the api links it, and the message saved rendered for the contact hovered (ADR 0144).
 */
export const Route = createFileRoute('/_app/campaigns/$campaignId')({
  params: uuidParams('campaignId'),
  loader: async ({ context, params }) => {
    const campaign = await context.queryClient
      .ensureQueryData(campaignQuery(params.campaignId))
      .catch(notFoundWhenTheApiSaysSo)
    await context.queryClient.ensureInfiniteQueryData(
      taggedContactSlicesQuery(searchOfTags(campaign.targetedTags)),
    )
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
  const target = useSuspenseInfiniteQuery(taggedContactSlicesQuery(searchOfTags(campaign.targetedTags)))
  const revise = useReviseCampaign()
  const [changed, setChanged] = useState(false)

  return (
    <div className="space-y-6">
      <BackButton fallback="/campaigns" />
      <PageHeader
        title={campaign.name}
        description={t(`campaigns.status.${campaign.status}`)}
        actions={
          <CampaignMenu
            campaign={campaign}
            onDiscarded={() => navigate({ to: '/campaigns', replace: true })}
          />
        }
      />
      <div className="grid gap-6 lg:grid-cols-2">
        <CampaignForm
          values={revisionOf(campaign)}
          submitLabel={t('common.save')}
          tagsField={(chosen, onChange) => <TagsFilter chosen={chosen} onChange={onChange} />}
          onChanged={setChanged}
          onSubmit={(body) => revise.mutateAsync({ params: { path: { id: campaign.id } }, body })}
        />
        <CampaignTarget
          key={campaign.targetedTags.join(' ')}
          campaignId={campaign.id}
          contacts={target.data.pages.flatMap((slice) => slice.items)}
          found={target.data.pages[0]?.found ?? 0}
          changed={changed}
          loadMore={target.hasNextPage ? () => void target.fetchNextPage() : undefined}
        />
      </div>
    </div>
  )
}
