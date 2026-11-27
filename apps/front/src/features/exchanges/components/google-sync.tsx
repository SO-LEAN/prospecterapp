import { useQuery } from '@tanstack/react-query'
import { RefreshCw } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { describeTheSync, SyncMarker } from '@/shared/ui/event/sync-marker'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/shared/ui/tooltip'
import { useRetryGooglePush } from '../mutations'
import { googleSyncQuery } from '../queries'

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

  return (
    <div className="flex flex-wrap items-center gap-2 text-muted-foreground text-xs">
      <Tooltip>
        <TooltipTrigger asChild>
          <SyncMarker sync={data.sync} />
        </TooltipTrigger>
        <TooltipContent>{describeTheSync(data.sync, t)}</TooltipContent>
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
