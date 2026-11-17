import { Link } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { memberName } from '@/shared/lib/member-name'
import { handledIn } from '../progress'
import type { Campaign } from '../queries'
import { CampaignStatusBadge } from './campaign-status-badge'

/** The campaigns of the team: their status, their channel, their author and how far they went. */
export function CampaignList({ campaigns }: { campaigns: Campaign[] }) {
  const { t } = useTranslation()

  return (
    <ul className="divide-y rounded-xl border bg-card">
      {campaigns.map((campaign) => (
        <li key={campaign.id} className="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3">
          <Link
            to="/campaigns/$campaignId"
            params={{ campaignId: campaign.id }}
            className="min-w-0 flex-1 truncate font-medium hover:underline"
          >
            {campaign.name}
          </Link>
          <CampaignStatusBadge status={campaign.status} />
          <span className="text-muted-foreground text-sm">{t(`campaigns.channels.${campaign.channel}`)}</span>
          <span className="text-muted-foreground text-sm">
            {t('campaigns.by', { name: memberName(campaign.author) })}
          </span>
          <span
            className="text-sm tabular-nums"
            title={t('campaigns.progressLabel', {
              handled: handledIn(campaign),
              targeted: campaign.targeted,
            })}
          >
            {t('campaigns.progress', { handled: handledIn(campaign), targeted: campaign.targeted })}
          </span>
        </li>
      ))}
    </ul>
  )
}
