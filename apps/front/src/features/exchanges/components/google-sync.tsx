import { useQuery } from '@tanstack/react-query'
import type { TFunction } from 'i18next'
import { CircleAlert, CircleCheck, Clock, RefreshCw } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { formatMoment } from '@/shared/lib/format'
import { Button } from '@/shared/ui/button'
import { GoogleLogo } from '@/shared/ui/google-logo'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/shared/ui/tooltip'
import { useRetryGooglePush } from '../mutations'
import { googleSyncQuery, type GoogleSync as Sync } from '../queries'

type State = NonNullable<Sync['sync']>

const ICONS = {
  synced: <CircleCheck aria-hidden className="size-3.5 text-success" />,
  pending: <Clock aria-hidden className="size-3.5 text-muted-foreground" />,
  failed: <CircleAlert aria-hidden className="size-3.5 text-warning" />,
} as const

/**
 * Where my planned exchange's push into my Google calendar stands (ADR 0133): synced, pending, or failed with
 * « Réessayer ». A hover shows its reason and its moment. It shows nothing while the exchange is not pushed into my
 * calendar.
 */
export function GoogleSync({ exchangeId }: { exchangeId: string }) {
  const { t } = useTranslation()
  const { data } = useQuery(googleSyncQuery(exchangeId))
  const retry = useRetryGooglePush()

  if (!data?.sync) {
    return null
  }

  const said = describe(data.sync, t)

  return (
    <div className="flex flex-wrap items-center gap-2 text-muted-foreground text-xs">
      <Tooltip>
        <TooltipTrigger asChild>
          <span role="img" aria-label={said} className="flex items-center gap-1">
            <GoogleLogo className="size-4" />
            {ICONS[data.sync.state]}
          </span>
        </TooltipTrigger>
        <TooltipContent>{said}</TooltipContent>
      </Tooltip>
      {data._links.retry && (
        <Button
          variant="outline"
          size="sm"
          disabled={retry.isPending}
          onClick={() => retry.mutate({ params: { path: { id: exchangeId } } })}
        >
          <RefreshCw aria-hidden /> {t('common.retry')}
        </Button>
      )}
    </div>
  )
}

function describe(sync: State, t: TFunction): string {
  const at = sync.at ? formatMoment(sync.at) : ''

  if (sync.state === 'synced') return t('exchanges.googleSync.synced', { at })
  if (sync.state === 'failed') return t('exchanges.googleSync.failed', { at })
  return t(
    sync.reason === 'toReconnect'
      ? 'exchanges.googleSync.pendingToReconnect'
      : 'exchanges.googleSync.pending',
  )
}
