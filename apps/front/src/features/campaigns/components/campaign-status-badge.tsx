import { useTranslation } from 'react-i18next'
import { Badge } from '@/shared/ui/badge'
import type { Campaign } from '../queries'

/** Where a campaign is in its life, shown the same way everywhere. */
export function CampaignStatusBadge({ status }: { status: Campaign['status'] }) {
  const { t } = useTranslation()

  return (
    <Badge variant={status === 'started' ? 'default' : 'secondary'}>{t(`campaigns.status.${status}`)}</Badge>
  )
}
