import { useTranslation } from 'react-i18next'
import { handledIn } from '../progress'
import type { Campaign } from '../queries'

/** How far a started campaign went: its recipients handled, processed or dismissed, out of all of them. */
export function CampaignProgress({ campaign }: { campaign: Campaign }) {
  const { t } = useTranslation()
  const counts = { handled: handledIn(campaign), targeted: campaign.targeted }

  return (
    <span className="text-sm tabular-nums" title={t('campaigns.progressLabel', counts)}>
      {t('campaigns.progress', counts)}
    </span>
  )
}
