import { useTranslation } from 'react-i18next'
import { Badge } from '@/shared/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { Detail } from '@/shared/ui/detail'
import type { Campaign } from '../queries'

/** A started campaign, read only: nothing of it changes any more, it is led as it was frozen. */
export function CampaignSummary({ campaign }: { campaign: Campaign }) {
  const { t } = useTranslation()

  return (
    <Card>
      <CardHeader>
        <CardTitle>
          <h2>{campaign.name}</h2>
        </CardTitle>
      </CardHeader>
      <CardContent className="text-sm">
        <Detail label={t('campaigns.fields.channel')}>{t(`exchanges.channels.${campaign.channel}`)}</Detail>
        <Detail label={t('campaigns.fields.tags')}>
          <span className="flex flex-wrap gap-1.5">
            {campaign.tags.map((tag) => (
              <Badge key={tag} variant="secondary">
                {tag}
              </Badge>
            ))}
          </span>
        </Detail>
        {campaign.channel === 'email' && (
          <Detail label={t('campaigns.fields.subject')}>{campaign.subject}</Detail>
        )}
        <Detail label={t('campaigns.fields.message')}>
          <span className="whitespace-pre-wrap">{campaign.message}</span>
        </Detail>
        <Detail label={t('campaigns.fields.messageMode')}>
          {t(`campaigns.fields.messageModes.${campaign.messageMode}`)}
        </Detail>
      </CardContent>
    </Card>
  )
}
