import { ArrowDownLeft, ArrowUpRight, type LucideIcon } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { CHANNEL_ICONS, type Channel } from './channel'
import { FAMILY_OF_CHANNEL, SOFT_TINTS } from './family'

type Direction = 'inbound' | 'outbound'

const DIRECTION_ICONS: Record<Direction, LucideIcon> = {
  inbound: ArrowDownLeft,
  outbound: ArrowUpRight,
}

/** An exchange's channel, as an icon in a 26 px tile tinted with its family, and its direction as an arrow. */
export function TypeTile({
  channel,
  direction,
  className,
}: {
  channel: Channel
  direction: Direction
  className?: string
}) {
  const { t } = useTranslation()
  const ChannelIcon = CHANNEL_ICONS[channel]
  const DirectionIcon = DIRECTION_ICONS[direction]

  return (
    <span
      role="img"
      aria-label={t('events.tile', {
        channel: t(`exchanges.channels.${channel}`),
        direction: t(`events.directions.${direction}`),
      })}
      className={cn(
        'relative inline-flex size-[26px] shrink-0 items-center justify-center rounded-md',
        SOFT_TINTS[FAMILY_OF_CHANNEL[channel]],
        className,
      )}
    >
      <ChannelIcon aria-hidden className="size-3.5" />
      <DirectionIcon aria-hidden className="absolute -right-0.5 -bottom-0.5 size-2.5 rounded-full bg-card" />
    </span>
  )
}
