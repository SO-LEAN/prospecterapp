import { Ban, CalendarClock, CircleCheck, PhoneMissed } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { useCancelExchange, useMarkExchangeMissed, useUndoable } from '../mutations'
import type { Exchange } from '../queries'
import type { StartWith } from './exchange-detail'

type Props = {
  exchange: Pick<Exchange, 'id' | '_links'>
  /** Opens the exchange's detail, on its report or on its move. */
  onOpen: (startWith: StartWith) => void
  /** Takes the line away while its gesture can be taken back, and returns the function that puts it back. */
  onLeave?: () => () => void
}

/** A line that stays in place while its gesture can be taken back has nothing to put back. */
const stayingInPlace = () => () => undefined

/** What an exchange's line offers at hand, as its links allow. */
export function ExchangeGestures({ exchange, onOpen, onLeave = stayingInPlace }: Props) {
  const { t } = useTranslation()
  const { _links } = exchange

  return (
    <div className="flex flex-wrap gap-2 empty:hidden">
      {_links.markDone && (
        <Button size="sm" onClick={() => onOpen('report')}>
          <CircleCheck aria-hidden /> {t('exchanges.handling.done')}
        </Button>
      )}
      {_links.markMissed && <MarkMissed exchangeId={exchange.id} onLeave={onLeave} />}
      {_links.cancel && <Cancel exchangeId={exchange.id} onLeave={onLeave} />}
      {_links.move && (
        <Button variant="outline" size="sm" onClick={() => onOpen('move')}>
          <CalendarClock aria-hidden /> {t('exchanges.handling.move')}
        </Button>
      )}
    </div>
  )
}

type GestureProps = { exchangeId: string; onLeave: NonNullable<Props['onLeave']> }

/** Marks the exchange missed once its toast no longer lets the member take it back. The line puts itself back on a refusal. */
function MarkMissed({ exchangeId, onLeave }: GestureProps) {
  const { t } = useTranslation()
  const markMissed = useMarkExchangeMissed()
  const undoable = useUndoable()

  return (
    <Button
      variant="outline"
      size="sm"
      onClick={() => {
        const back = onLeave()
        undoable(
          t('exchanges.handling.markedMissed'),
          () => markMissed.mutate({ params: { path: { id: exchangeId } } }, { onError: back }),
          back,
        )
      }}
    >
      <PhoneMissed aria-hidden /> {t('exchanges.handling.missed')}
    </Button>
  )
}

/**
 * Cancels the exchange, without saying who called it off, once its toast no longer lets the member take it back. The
 * line puts itself back on a refusal.
 */
function Cancel({ exchangeId, onLeave }: GestureProps) {
  const { t } = useTranslation()
  const cancel = useCancelExchange()
  const undoable = useUndoable()

  return (
    <Button
      variant="outline"
      size="sm"
      onClick={() => {
        const back = onLeave()
        undoable(
          t('exchanges.handling.cancelled'),
          () =>
            cancel.mutate(
              { params: { path: { id: exchangeId } }, body: { canceller: null } },
              { onError: back },
            ),
          back,
        )
      }}
    >
      <Ban aria-hidden /> {t('toProcess.cancelled')}
    </Button>
  )
}
