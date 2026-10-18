import {
  ArrowDownLeft,
  ArrowUpRight,
  BriefcaseBusiness,
  CalendarDays,
  type LucideIcon,
  Mail,
  MessageCircle,
  MessageSquareText,
  Phone,
  PhoneMissed,
} from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { formatMoment } from '@/shared/lib/format'
import type { Channel, Exchange } from '../queries'

export const CHANNEL_ICONS: Record<Channel, LucideIcon> = {
  call: Phone,
  sms: MessageSquareText,
  email: Mail,
  linkedIn: BriefcaseBusiness,
  meeting: CalendarDays,
  other: MessageCircle,
}

const directionIconOf = (exchange: Exchange) =>
  exchange.missed ? PhoneMissed : exchange.direction === 'inbound' ? ArrowDownLeft : ArrowUpRight

/** One exchange of the timeline: its channel, its direction (missed for a call that did not go through), when, and the beginning of its text. */
export function ExchangeLine({ exchange }: { exchange: Exchange }) {
  const { t } = useTranslation()
  const ChannelIcon = CHANNEL_ICONS[exchange.channel]
  const DirectionIcon = directionIconOf(exchange)
  const direction = exchange.missed ? t('exchanges.missed') : t(`exchanges.directions.${exchange.direction}`)

  return (
    <div className="flex items-start gap-3">
      <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-accent text-accent-foreground">
        <ChannelIcon aria-hidden className="size-4" />
      </span>
      <div className="min-w-0 flex-1">
        <p className="flex items-center gap-1.5 font-medium">
          {t('exchanges.summary', { channel: t(`exchanges.channels.${exchange.channel}`), direction })}
          <DirectionIcon
            aria-hidden
            className={exchange.missed ? 'size-4 text-destructive' : 'size-4 text-muted-foreground'}
          />
        </p>
        <p className="text-muted-foreground text-xs">
          <time dateTime={exchange.occurredAt}>{formatMoment(exchange.occurredAt)}</time>
        </p>
        {exchange.text && <p className="truncate">{exchange.text}</p>}
      </div>
    </div>
  )
}
