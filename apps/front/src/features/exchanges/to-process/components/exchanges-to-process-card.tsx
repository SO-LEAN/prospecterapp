import { useSuspenseQuery } from '@tanstack/react-query'
import { Ban, CalendarClock, CircleCheck, PhoneMissed } from 'lucide-react'
import { type ReactNode, useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { ToDoLine } from '@/features/to-dos/components/to-dos-section'
import { formatMoment } from '@/shared/lib/format'
import { cn } from '@/shared/lib/utils'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { ExchangeDialog } from '../../calendar/components/exchange-dialog'
import type { StartWith } from '../../components/exchange-detail'
import { CHANNEL_ICONS } from '../../components/exchange-line'
import { useCancelExchange, useMarkExchangeMissed, useUndoable } from '../../mutations'
import { useNow } from '../../use-now'
import { countOf, type ExchangeToProcess, exchangesToProcessQuery, type ToDoToProcess } from '../queries'

type Opened = { item: ExchangeToProcess; startWith?: StartWith }

type Props = {
  /** Who a contact is, their card in short, in the exchange opened: told by the page composing the contacts. */
  contactSummary: (contactId: string) => ReactNode
}

/**
 * What I planned whose end has passed and I have not handled yet, and my to-dos of the day and late, on the dashboard
 * while there is some: each exchange marked done from its report, missed or cancelled at once, taken back by its
 * toast, or moved; each to-do ticked off in place. The dialog of an exchange outlives the card, so that what follows
 * its report is planned once the last one is done.
 */
export function ExchangesToProcessCard({ contactSummary }: Props) {
  const { t } = useTranslation()
  const { data: toProcess } = useSuspenseQuery(exchangesToProcessQuery())
  const [opened, setOpened] = useState<Opened | null>(null)
  const leaving = useLeavingLines()
  const processed = useEverythingProcessedTold(countOf(toProcess) === 0)

  const open = (item: ExchangeToProcess, startWith?: StartWith) => {
    processed.started()
    setOpened({ item, startWith })
  }

  return (
    <>
      {countOf(toProcess) > 0 && (
        <Card className="border-warning">
          <CardHeader>
            <CardTitle className="text-base">{t('toProcess.title')}</CardTitle>
          </CardHeader>
          <CardContent>
            <ul className="space-y-2">
              {toProcess.items.map((item) => (
                <ExchangeToProcessLine
                  key={item.exchange.id}
                  item={item}
                  leaving={leaving.has(item.exchange.id)}
                  onOpen={(startWith) => open(item, startWith)}
                  onLeave={() => {
                    processed.started()
                    return leaving.add(item.exchange.id)
                  }}
                />
              ))}
            </ul>
            <ToDosToProcess toDos={toProcess.toDos} onTickedOff={processed.started} />
          </CardContent>
        </Card>
      )}
      <ExchangeDialog
        item={opened?.item ?? null}
        startWith={opened?.startWith}
        onClose={() => setOpened(null)}
        contactSummary={contactSummary}
      />
    </>
  )
}

/** My to-dos of the day and late, the most overdue first, each saying since when. */
function ToDosToProcess({ toDos, onTickedOff }: { toDos: ToDoToProcess[]; onTickedOff: () => void }) {
  const { t } = useTranslation()
  const now = useNow()

  if (toDos.length === 0) return null
  return (
    <section aria-label={t('toProcess.toDos')} className="mt-3 space-y-2">
      <h3 className="font-medium text-muted-foreground text-sm">{t('toProcess.toDos')}</h3>
      <ul className="space-y-2">
        {toDos.map(({ toDo, daysOverdue }) => (
          <li
            key={toDo.id}
            data-late={daysOverdue > 0 ? '' : undefined}
            className={cn(
              'space-y-1 rounded-lg border p-3',
              daysOverdue > 0 && 'border-l-4 border-l-warning',
            )}
          >
            <p className="font-medium text-xs">
              {daysOverdue > 0 ? t('toProcess.overdue', { count: daysOverdue }) : t('toProcess.forToday')}
            </p>
            <ToDoLine toDo={toDo} now={now} withTheirContact onTickedOff={onTickedOff} />
          </li>
        ))}
      </ul>
    </section>
  )
}

/** The lines handled, taken away at once while their gesture may still be taken back. */
function useLeavingLines() {
  const [leaving, setLeaving] = useState<ReadonlySet<string>>(new Set())

  return {
    has: (id: string) => leaving.has(id),
    /** Takes it away; what is given back puts it back, its gesture taken back. */
    add: (id: string) => {
      setLeaving((ids) => new Set(ids).add(id))
      return () =>
        setLeaving((ids) => {
          const back = new Set(ids)
          back.delete(id)
          return back
        })
    },
  }
}

/** « Tout est à jour 🎉 », once what I started processing on the page leaves nothing to process. */
function useEverythingProcessedTold(nothingLeft: boolean) {
  const { t } = useTranslation()
  const [processing, setProcessing] = useState(false)

  useEffect(() => {
    if (!processing || !nothingLeft) return
    toast.success(t('toProcess.allDone'))
    setProcessing(false)
  }, [processing, nothingLeft, t])

  return { started: () => setProcessing(true) }
}

type LineProps = {
  item: ExchangeToProcess
  leaving: boolean
  onOpen: (startWith?: StartWith) => void
  /** Takes the line away, and gives back what puts it back. */
  onLeave: () => () => void
}

/** When it was, its channel, with whom and their organization; what it offers at hand. */
function ExchangeToProcessLine({ item, leaving, onOpen, onLeave }: LineProps) {
  const { t } = useTranslation()
  const { exchange, interlocutor, organization } = item
  const ChannelIcon = CHANNEL_ICONS[exchange.channel]

  return (
    <li
      aria-hidden={leaving || undefined}
      inert={leaving}
      className={cn(
        'space-y-2 rounded-lg border p-3',
        leaving && 'fade-out-0 slide-out-to-right-4 animate-out fill-mode-forwards duration-300',
      )}
    >
      <button
        type="button"
        onClick={() => onOpen()}
        className="block w-full min-w-0 rounded-md text-left outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
      >
        <span className="flex items-center gap-2 text-sm">
          <span className="font-medium tabular-nums">{formatMoment(exchange.occurredAt)}</span>
          <ChannelIcon aria-hidden className="size-4 shrink-0 text-muted-foreground" />
          <span>{t(`exchanges.channels.${exchange.channel}`)}</span>
        </span>
        <span className="block truncate font-medium">{interlocutor.name ?? t('calendar.nobody')}</span>
        {organization && (
          <span className="block truncate text-muted-foreground text-sm">{organization.name}</span>
        )}
      </button>
      <div className="flex flex-wrap gap-2 empty:hidden">
        {exchange._links.markDone && (
          <Button size="sm" onClick={() => onOpen('report')}>
            <CircleCheck aria-hidden /> {t('exchanges.handling.done')}
          </Button>
        )}
        {exchange._links.markMissed && <MarkMissed exchangeId={exchange.id} onLeave={onLeave} />}
        {exchange._links.cancel && <Cancel exchangeId={exchange.id} onLeave={onLeave} />}
        {exchange._links.move && (
          <Button variant="outline" size="sm" onClick={() => onOpen('move')}>
            <CalendarClock aria-hidden /> {t('exchanges.handling.move')}
          </Button>
        )}
      </div>
    </li>
  )
}

type GestureProps = { exchangeId: string; onLeave: LineProps['onLeave'] }

/** Marked missed once its toast no longer lets it be taken back, the line taken away meanwhile, back when refused. */
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
 * Cancelled without saying by whom, once its toast no longer lets it be taken back, the line taken away meanwhile,
 * back when refused.
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
