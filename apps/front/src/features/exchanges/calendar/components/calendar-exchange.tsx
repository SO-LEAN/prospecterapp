import { TriangleAlert } from 'lucide-react'
import type { ComponentProps, ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { formatHour } from '@/shared/lib/format'
import { cn } from '@/shared/lib/utils'
import { CHANNEL_ICONS } from '../../components/exchange-line'
import { ToAttachBadge } from '../../components/to-attach-badge'
import { isToHandle } from '../dates'
import type { CalendarExchange } from '../queries'

/** How an exchange looks, by what became of it: to come in full colour, done faded, missed hatched, cancelled struck. */
const LOOKS = {
  planned: 'bg-primary text-primary-foreground',
  toHandle: 'bg-warning text-foreground',
  done: 'bg-muted text-muted-foreground',
  missed:
    'border border-dashed bg-[repeating-linear-gradient(135deg,var(--color-muted)_0_5px,transparent_5px_10px)] text-muted-foreground',
  cancelled: 'border text-muted-foreground line-through',
} as const

export type Look = keyof typeof LOOKS

export function lookOf({ exchange }: CalendarExchange, now: Date): Look {
  return isToHandle(exchange, now) ? 'toHandle' : exchange.status
}

type Props = Omit<ComponentProps<'button'>, 'children'> & {
  item: CalendarExchange
  now: Date
  /** Grabbed to be dragged, in a view handling the gestures, when the API lets it move. */
  movable?: boolean
  /** Being dragged: lifted above the others. */
  dragged?: boolean
  /** Its slot ended without it being processed, as the API says (ADR 0134). */
  late?: boolean
  children?: ReactNode
}

/**
 * An exchange of the calendar: its hour, its channel and who it is with, in the look of its status; late, an icon and
 * « En retard » beside the colour, with one pulse when shown. A click opens it, one the API lets move is grabbed to be
 * dragged.
 */
export function CalendarExchangeChip({
  item,
  now,
  movable = false,
  dragged = false,
  late = false,
  className,
  children,
  ...button
}: Props) {
  const { t } = useTranslation()
  const { exchange, interlocutor } = item
  const ChannelIcon = CHANNEL_ICONS[exchange.channel]
  const look = lookOf(item, now)
  const name = interlocutor.name ?? t('calendar.nobody')
  const status = late
    ? t('calendar.late')
    : look === 'toHandle'
      ? t('calendar.toHandle')
      : t(`exchanges.statuses.${exchange.status}`)
  const label = t('calendar.described', {
    hour: formatHour(exchange.occurredAt),
    channel: t(`exchanges.channels.${exchange.channel}`),
    name,
    status,
  })

  return (
    <button
      type="button"
      aria-label={label}
      data-movable={movable && exchange._links.move ? '' : undefined}
      data-late={late ? '' : undefined}
      className={cn(
        'relative block w-full min-w-0 select-none overflow-hidden rounded-md px-1.5 py-0.5 text-left text-xs leading-5 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 data-movable:cursor-grab data-movable:touch-none',
        LOOKS[look],
        late && 'motion-safe:animate-late-once',
        dragged && 'z-20 cursor-grabbing opacity-90 shadow-lg ring-2 ring-ring',
        className,
      )}
      {...button}
    >
      <span className="flex min-w-0 items-center gap-1">
        <ChannelIcon aria-hidden className="size-3 shrink-0" />
        <span className="shrink-0 tabular-nums">{formatHour(exchange.occurredAt)}</span>
        <span className="truncate font-medium">{name}</span>
        <ToAttachBadge exchange={exchange} />
        {late && <TriangleAlert aria-hidden className="ms-auto size-3 shrink-0" />}
      </span>
      {children}
    </button>
  )
}
