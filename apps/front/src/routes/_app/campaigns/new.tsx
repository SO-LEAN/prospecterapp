import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { CampaignForm } from '@/features/campaigns/components/campaign-form'
import { CampaignTarget } from '@/features/campaigns/components/campaign-target'
import { usePrepareCampaign } from '@/features/campaigns/mutations'
import { campaignsQuery } from '@/features/campaigns/queries'
import { newCampaign } from '@/features/campaigns/schema'
import { useLiveTarget } from '@/features/campaigns/use-live-target'
import { TagsFilter } from '@/features/tags/components/tags-filter'
import { useGoBack } from '@/shared/hooks/use-go-back'
import { ErrorState } from '@/shared/ui/feedback/error-state'
import { PageHeader } from '@/shared/ui/page-header'
import { PageSkeleton } from '@/shared/ui/page-skeleton'

/** The form opens only for members whom the API lets read the campaigns. The others learn that their role does not allow it. */
export const Route = createFileRoute('/_app/campaigns/new')({
  loader: ({ context }) => context.queryClient.ensureQueryData(campaignsQuery()),
  pendingComponent: () => <PageSkeleton rows={4} />,
  errorComponent: ({ error, reset }) => <ErrorState error={error} reset={reset} />,
  component: NewCampaign,
})

/**
 * A campaign prepared as a draft. Its form is on the left. On the right, the page shows the contacts it targets as the
 * member types. The message's preview comes once the campaign is prepared, on its own page: the core renders the
 * saved message, never the screen.
 */
function NewCampaign() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const cancel = useGoBack(() => navigate({ to: '/campaigns', replace: true }))
  const prepare = usePrepareCampaign()
  const empty = newCampaign()
  const target = useLiveTarget(empty.tags, empty.channel)

  return (
    <div className="space-y-6">
      <PageHeader title={t('campaigns.new.title')} />
      <div className="grid gap-6 lg:grid-cols-2">
        <CampaignForm
          values={empty}
          submitLabel={t('campaigns.new.submit')}
          withDailyCap
          onCancel={cancel}
          tagsField={(chosen, onChange) => <TagsFilter chosen={chosen} onChange={onChange} />}
          onTargetingChange={target.follow}
          onSubmit={async (body) => {
            const campaign = await prepare.mutateAsync({ body })
            await navigate({
              to: '/campaigns/$campaignId',
              params: { campaignId: campaign.id },
              replace: true,
            })
          }}
        />
        <div className="space-y-2">
          <CampaignTarget target={target} changed={false} />
          <p className="text-muted-foreground text-sm">{t('campaigns.target.beforePreparing')}</p>
        </div>
      </div>
    </div>
  )
}
