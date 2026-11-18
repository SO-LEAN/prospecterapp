import { Link } from '@tanstack/react-router'
import { Megaphone } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import type { components } from '@/shared/api/schema'
import { Badge } from '@/shared/ui/badge'

type Campaign = components['schemas']['CampaignOfExchangeResource']

/**
 * The campaign an exchange was noted in, as Campaigns names it now (ADR 0145): leading to it when the reader may read
 * it, said erased once the operations erased it (ADR 0152).
 */
export function CampaignOfTheExchange({ campaign, linked }: { campaign: Campaign; linked: boolean }) {
  const { t } = useTranslation()

  if (campaign.name === null || campaign.name === undefined) {
    return <Badge variant="outline">{t('exchanges.campaign.erased')}</Badge>
  }
  const named = (
    <>
      <Megaphone aria-hidden />
      {t('exchanges.campaign.named', { name: campaign.name })}
    </>
  )

  return linked ? (
    <Badge variant="secondary" asChild>
      <Link to="/campaigns/$campaignId" params={{ campaignId: campaign.id }}>
        {named}
      </Link>
    </Badge>
  ) : (
    <Badge variant="secondary">{named}</Badge>
  )
}
