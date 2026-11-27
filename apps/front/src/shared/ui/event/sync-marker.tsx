import type { TFunction } from 'i18next'
import { CircleAlert, CircleCheck, Clock } from 'lucide-react'
import type { ComponentProps } from 'react'
import { useTranslation } from 'react-i18next'
import type { components } from '@/shared/api/schema'
import { formatMoment } from '@/shared/lib/format'
import { cn } from '@/shared/lib/utils'
import { GoogleLogo } from '@/shared/ui/google-logo'

export type Sync = components['schemas']['SyncResource']

const ICONS = {
  synced: <CircleCheck aria-hidden className="size-3.5 text-success" />,
  pending: <Clock aria-hidden className="size-3.5 text-muted-foreground" />,
  failed: <CircleAlert aria-hidden className="size-3.5 text-warning" />,
} as const

/**
 * Where an exchange's push into its member's Google calendar stands (ADR 0133): synced, pending or failed. It says
 * why and since when to screen readers.
 */
export function SyncMarker({ sync, className, ...span }: ComponentProps<'span'> & { sync: Sync }) {
  const { t } = useTranslation()

  return (
    <span
      role="img"
      aria-label={describeTheSync(sync, t)}
      className={cn('flex shrink-0 items-center gap-1', className)}
      {...span}
    >
      <GoogleLogo className="size-4" />
      {ICONS[sync.state]}
    </span>
  )
}

/** What the push stands at, in words: its state, since when, and why it waits. */
export function describeTheSync(sync: Sync, t: TFunction): string {
  const at = sync.at ? formatMoment(sync.at) : ''

  if (sync.state === 'synced') return t('events.markers.sync.synced', { at })
  if (sync.state === 'failed') return t('events.markers.sync.failed', { at })
  return t(
    sync.reason === 'toReconnect' ? 'events.markers.sync.pendingToReconnect' : 'events.markers.sync.pending',
  )
}
