import { useSuspenseQuery } from '@tanstack/react-query'
import { Ban, CalendarClock, CircleCheck, PhoneMissed } from 'lucide-react'
import { type ReactNode, useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { formatMoment } from '@/shared/lib/format'
import { cn } from '@/shared/lib/utils'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { ExchangeDialog } from '../../calendar/components/exchange-dialog'
import type { StartWith } from '../../components/exchange-detail'
import { CHANNEL_ICONS } from '../../components/exchange-line'
import { ToAttachBadge } from '../../components/to-attach-badge'
import { useCancelExchange, useMarkExchangeMissed, useUndoable } from '../../mutations'
import {
  type ContactsToAttach,
  countOf,
  type ExchangeToProcess,
  exchangesToProcessQuery,
  type ToDoToProcess,
} from '../queries'

type Opened = { item: ExchangeToProcess; startWith?: StartWith }

/**
 * A to-do's line, ticked off in place, which says since when it waits. The page that composes the to-dos gives it,
 * because a feature never imports another.
 */
export type ToDoLineOf = (toDo: ToDoToProcess, onTickedOff: () => void) => ReactNode

type Props = {
  /** Who a contact is, a short version of their card, in the opened exchange. The page that composes the contacts gives it. */
  contactSummary: (contactId: string) => ReactNode
  toDoLine: ToDoLineOf
  /** The contacts I hold to attach. The page that composes them gives them (ADR 0146). */
  contactsToAttach?: (contacts: ContactsToAttach) => ReactNode
}

/**
 * On the dashboard, while there are any: the exchanges I planned whose end has passed and that I have not handled
 * yet, my to-dos due today and the late ones, and the contacts I hold to attach. I mark each exchange done from its
 * report, or missed or cancelled at once, which its toast can take back. I can also move it. I tick each to-do off in
 * place. An exchange's dialog outlives the card, so I can plan what follows its report once the last exchange is
 * done.
 */
export function ExchangesToProcessCard({ contactSummary, toDoLine, contactsToAttach }: Props) {
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
            <ul className="space-y-2 empty:hidden">
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
            <ToDosToProcess toDos={toProcess.toDos} toDoLine={toDoLine} onTickedOff={processed.started} />
            {toProcess.contactsToAttach.length > 0 && contactsToAttach?.(toProcess.contactsToAttach)}
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

/** My to-dos due today and the late ones, the most overdue first. */
type ToDosProps = { toDos: ToDoToProcess[]; toDoLine: ToDoLineOf; onTickedOff: () => void }

function ToDosToProcess({ toDos, toDoLine, onTickedOff }: ToDosProps) {
  const { t } = useTranslation()

  if (toDos.length === 0) return null
  return (
    <section aria-label={t('toProcess.toDos')} className="mt-3 space-y-2">
      <h3 className="font-medium text-muted-foreground text-sm">{t('toProcess.toDos')}</h3>
      <ul className="space-y-2">
        {toDos.map((toDo) => (
          <li key={toDo.toDo.id}>{toDoLine(toDo, onTickedOff)}</li>
        ))}
      </ul>
    </section>
  )
}

/** The handled lines, taken away at once while their gesture can still be taken back. */
function useLeavingLines() {
  const [leaving, setLeaving] = useState<ReadonlySet<string>>(new Set())

  return {
    has: (id: string) => leaving.has(id),
    /** Takes the line away. The returned function puts it back when its gesture is taken back. */
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

/** « Tout est à jour 🎉 », once what I processed on the page leaves nothing to process. */
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
  /** Takes the line away, and returns the function that puts it back. */
  onLeave: () => () => void
}

/** When the exchange was, its channel, who it was with and their organization, and the gestures it offers at hand. */
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
        <span className="flex min-w-0 items-center gap-1.5">
          <span className="truncate font-medium">{interlocutor.name ?? t('calendar.nobody')}</span>
          <ToAttachBadge exchange={exchange} />
        </span>
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

/** Marks the exchange missed once its toast no longer lets the member take it back. The line goes away meanwhile, and comes back on a refusal. */
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
 * line goes away meanwhile, and comes back on a refusal.
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
