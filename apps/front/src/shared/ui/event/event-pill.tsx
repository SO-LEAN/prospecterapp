import type { ReactNode } from 'react'
import { cn } from '@/shared/lib/utils'
import { CHANNEL_ICONS, type Channel } from './channel'
import {
  type ExchangeFamily,
  FAMILY_OF_CHANNEL,
  LATE_EDGE,
  SOFT_TINTS,
  SOLID_TINTS,
  TEXT_TINTS,
} from './family'
import { type ExchangeStatus, StatusMark } from './status-mark'

/** Each status's material, on its family's tint: the colour says the channel, the material says the status. */
const LOOKS: Record<ExchangeStatus, (family: ExchangeFamily) => string> = {
  planned: (family) => SOLID_TINTS[family],
  done: (family) => SOFT_TINTS[family],
  missed: (family) => cn('missed-hatching border-l-2 border-destructive', TEXT_TINTS[family]),
  cancelled: () => 'border border-dashed border-muted-foreground/60 text-muted-foreground line-through',
}

/**
 * A calendar event: its channel's icon, its status's mark and its content, in its family's tint and its status's
 * material. A late one has a red edge.
 */
export function EventPill({
  channel,
  status,
  late = false,
  className,
  children,
}: {
  channel: Channel
  status: ExchangeStatus
  late?: boolean
  className?: string
  children?: ReactNode
}) {
  const ChannelIcon = CHANNEL_ICONS[channel]

  return (
    <span
      data-slot="event-pill"
      className={cn(
        'flex min-w-0 items-center gap-1 rounded-md px-1.5 py-0.5 text-xs leading-5',
        LOOKS[status](FAMILY_OF_CHANNEL[channel]),
        late && LATE_EDGE,
        className,
      )}
    >
      <ChannelIcon aria-hidden className="size-3 shrink-0" />
      <StatusMark status={status} late={late} />
      {children}
    </span>
  )
}
