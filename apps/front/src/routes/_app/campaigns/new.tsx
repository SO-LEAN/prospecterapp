import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { CampaignForm } from '@/features/campaigns/components/campaign-form'
import { usePrepareCampaign } from '@/features/campaigns/mutations'
import { newCampaign } from '@/features/campaigns/schema'
import { TagsFilter } from '@/features/tags/components/tags-filter'
import { useGoBack } from '@/shared/hooks/use-go-back'
import { PageHeader } from '@/shared/ui/page-header'

export const Route = createFileRoute('/_app/campaigns/new')({
  component: NewCampaign,
})

/**
 * A campaign prepared as a draft, its form at the left; its target and the preview of its message, at the right of
 * its own page, come once it is prepared: the core renders the message saved, never the screen.
 */
function NewCampaign() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const cancel = useGoBack(() => navigate({ to: '/campaigns', replace: true }))
  const prepare = usePrepareCampaign()

  return (
    <div className="space-y-6">
      <PageHeader title={t('campaigns.new.title')} />
      <div className="grid gap-6 lg:grid-cols-2">
        <CampaignForm
          values={newCampaign()}
          submitLabel={t('campaigns.new.submit')}
          onCancel={cancel}
          tagsField={(chosen, onChange) => <TagsFilter chosen={chosen} onChange={onChange} />}
          onSubmit={async (body) => {
            const campaign = await prepare.mutateAsync({ body })
            await navigate({
              to: '/campaigns/$campaignId',
              params: { campaignId: campaign.id },
              replace: true,
            })
          }}
        />
        <p className="text-muted-foreground text-sm">{t('campaigns.target.beforePreparing')}</p>
      </div>
    </div>
  )
}
