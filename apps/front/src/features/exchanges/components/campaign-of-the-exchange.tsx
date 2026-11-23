import { Link } from '@tanstack/react-router'
import { Megaphone } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import type { components } from '@/shared/api/schema'
import { Badge } from '@/shared/ui/badge'
import type { Exchange } from '../queries'

type Campaign = components['schemas']['CampaignOfExchangeResource']

/**
 * The campaign that an exchange was logged in, with its current name in Campaigns (ADR 0145). It links to the
 * campaign when the reader may read it. It says the campaign is erased once the operations erased it (ADR 0152).
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

/** The campaign of an exchange in a timeline. It links to the campaign when the API gives the link. */
export function CampaignOfTheLine({ exchange }: { exchange: Pick<Exchange, 'campaign' | '_links'> }) {
  return exchange.campaign ? (
    <CampaignOfTheExchange campaign={exchange.campaign} linked={Boolean(exchange._links.campaign)} />
  ) : null
}
