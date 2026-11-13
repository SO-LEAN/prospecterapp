import { Link } from '@tanstack/react-router'
import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { formatHour } from '@/shared/lib/format'
import { cn } from '@/shared/lib/utils'
import { CHANNEL_ICONS } from '../../components/exchange-line'
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

/**
 * An exchange of the calendar: its hour, its channel and who it is with, in the look of its status; it leads to the
 * card of the contact or of the organization, a forgotten contact to nothing.
 */
export function CalendarExchangeChip({
  item,
  now,
  className,
  children,
}: {
  item: CalendarExchange
  now: Date
  className?: string
  children?: ReactNode
}) {
  const { t } = useTranslation()
  const { exchange, interlocutor } = item
  const ChannelIcon = CHANNEL_ICONS[exchange.channel]
  const look = lookOf(item, now)
  const name = interlocutor.name ?? t('calendar.nobody')
  const status = look === 'toHandle' ? t('calendar.toHandle') : t(`exchanges.statuses.${exchange.status}`)
  const content = (
    <>
      <span className="flex min-w-0 items-center gap-1">
        <ChannelIcon aria-hidden className="size-3 shrink-0" />
        <span className="shrink-0 tabular-nums">{formatHour(exchange.occurredAt)}</span>
        <span className="truncate font-medium">{name}</span>
      </span>
      {children}
    </>
  )
  const label = t('calendar.described', {
    hour: formatHour(exchange.occurredAt),
    channel: t(`exchanges.channels.${exchange.channel}`),
    name,
    status,
  })
  const classes = cn(
    'block w-full min-w-0 overflow-hidden rounded-md px-1.5 py-0.5 text-left text-xs leading-5 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
    LOOKS[look],
    className,
  )

  if (interlocutor.presence === 'forgotten')
    return (
      <div role="note" aria-label={label} className={classes}>
        {content}
      </div>
    )
  return interlocutor.kind === 'person' ? (
    <Link
      to="/contacts/$contactId"
      params={{ contactId: interlocutor.id }}
      aria-label={label}
      className={classes}
    >
      {content}
    </Link>
  ) : (
    <Link
      to="/organizations/$organizationId"
      params={{ organizationId: interlocutor.id }}
      aria-label={label}
      className={classes}
    >
      {content}
    </Link>
  )
}
