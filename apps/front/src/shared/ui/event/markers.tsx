import { Link, Megaphone } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import type { components } from '@/shared/api/schema'
import { cn } from '@/shared/lib/utils'
import { Badge } from '@/shared/ui/badge'
import { GmailLogo } from '@/shared/ui/gmail-logo'
import { LATE_EDGE } from './family'
import { type Sync, SyncMarker } from './sync-marker'

type Exchange = components['schemas']['ExchangeResource']

export type Marked = Pick<
  Exchange,
  'createdByAnAI' | 'importedFrom' | 'campaign' | 'bookedByLink' | 'contactToAttach' | 'toRedact'
>

const MARKER = 'shrink-0 px-1 py-0 font-normal text-[0.65rem]'

/**
 * The discreet markers of an exchange. Each one shows only when the API gives its field, and never by its colour
 * alone: it has a text or an icon, and a name for screen readers. Its push into Google and whether it is late come
 * from other answers than the exchange's.
 */
export function ExchangeMarkers({
  exchange,
  sync,
  late = false,
}: {
  exchange: Marked
  sync?: Sync | null
  late?: boolean
}) {
  return (
    <>
      {late && <LateMarker />}
      {exchange.createdByAnAI && <AIMarker />}
      {exchange.importedFrom === 'google' && <GmailMarker />}
      {exchange.bookedByLink && <BookedByLinkMarker />}
      {exchange.contactToAttach && <ToAttachMarker />}
      {exchange.toRedact && <ToRedactMarker />}
      {exchange.campaign && <CampaignMarker name={exchange.campaign.name} />}
      {sync && <SyncMarker sync={sync} />}
    </>
  )
}

/** « En retard » with a red edge, on what is late. */
export function LateMarker() {
  const { t } = useTranslation()

  return (
    <Badge variant="outline" className={cn(MARKER, LATE_EDGE)}>
      {t('events.markers.late')}
    </Badge>
  )
}

/**
 * The campaign an exchange was logged in, by its current name in Campaigns (ADR 0145). It says the campaign is
 * erased once the operations erased it (ADR 0152).
 */
export function CampaignMarker({ name }: { name?: string | null }) {
  const { t } = useTranslation()

  if (!name) {
    return <Badge variant="outline">{t('events.markers.campaignErased')}</Badge>
  }
  return (
    <Badge variant="secondary">
      <Megaphone aria-hidden />
      {t('events.markers.campaign', { name })}
    </Badge>
  )
}

/** « À rattacher » on an exchange whose contact waits to be attached to an organization (ADR 0146). */
export function ToAttachMarker() {
  const { t } = useTranslation()

  return (
    <Badge variant="outline" className={cn(MARKER, 'border-warning')}>
      {t('events.markers.toAttach')}
    </Badge>
  )
}

/** A red outline on an exchange whose contact is forgotten, while its text or its files may hold their personal data. */
export function ToRedactMarker() {
  const { t } = useTranslation()

  return (
    <Badge variant="outline" className={cn(MARKER, 'border-destructive text-destructive')}>
      {t('events.markers.toRedact')}
    </Badge>
  )
}

/** Gmail's logo on an exchange imported from Gmail. */
export function GmailMarker() {
  const { t } = useTranslation()

  return (
    <span role="img" aria-label={t('events.markers.importedFromGmail')} className="inline-flex shrink-0">
      <GmailLogo className="size-3" />
    </span>
  )
}

/** A link on an exchange that its contact booked through its holder's public link. */
export function BookedByLinkMarker() {
  const { t } = useTranslation()

  return (
    <span
      role="img"
      aria-label={t('events.markers.bookedByLink')}
      title={t('events.markers.bookedByLink')}
      className="inline-flex shrink-0 text-muted-foreground"
    >
      <Link aria-hidden className="size-3" />
    </span>
  )
}

/** « IA » on what an AI created for the member through the MCP server. Prospecter never knows which AI. */
export function AIMarker() {
  const { t } = useTranslation()

  return (
    <Badge variant="outline" title={t('events.markers.createdByAnAI')} className={MARKER}>
      {t('events.markers.ai')}
    </Badge>
  )
}
