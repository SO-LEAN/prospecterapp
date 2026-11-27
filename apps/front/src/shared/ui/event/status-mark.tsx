import { Ban, Check, Clock, type LucideIcon, TriangleAlert, X } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import type { components } from '@/shared/api/schema'
import { cn } from '@/shared/lib/utils'

export type ExchangeStatus = components['schemas']['ExchangeResource']['status']

type Mark = ExchangeStatus | 'late'

const ICONS: Record<Mark, LucideIcon> = {
  planned: Clock,
  done: Check,
  missed: X,
  cancelled: Ban,
  late: TriangleAlert,
}

/**
 * What became of an exchange, as an icon that screen readers say, so that its colour never says it alone. A late
 * exchange says it is late, whatever its status.
 */
export function StatusMark({
  status,
  late = false,
  className,
}: {
  status: ExchangeStatus
  late?: boolean
  className?: string
}) {
  const { t } = useTranslation()
  const mark: Mark = late ? 'late' : status
  const Icon = ICONS[mark]

  return (
    <span
      role="img"
      aria-label={t(`events.statuses.${mark}`)}
      className={cn('inline-flex shrink-0', className)}
    >
      <Icon aria-hidden className="size-3" />
    </span>
  )
}
