import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import type { Campaign } from '../queries'

/**
 * A campaign led under a daily cap: its recipients processed today, on the member's clock, out of the cap; in orange
 * beyond it, the cap being a guard the member may go beyond.
 */
export function CampaignDailyCount({ campaign }: { campaign: Campaign }) {
  const { t } = useTranslation()
  const cap = campaign.dailyCap ?? null
  const processed = campaign.processedToday ?? null

  if (cap === null || processed === null) {
    return null
  }

  return (
    <span className={cn('text-sm tabular-nums', processed > cap && 'text-warning')}>
      {t('campaigns.dailyCap.today', { processed, cap })}
    </span>
  )
}
