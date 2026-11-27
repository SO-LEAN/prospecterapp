import { useSuspenseQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { ArrowRight, CalendarPlus, CircleCheck, PhoneMissed } from 'lucide-react'
import { type ReactNode, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { formatMoment, formatTimeLeft, formatUpcoming } from '@/shared/lib/format'
import { cn } from '@/shared/lib/utils'
import { Badge } from '@/shared/ui/badge'
import { Button } from '@/shared/ui/button'
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { Skeleton } from '@/shared/ui/skeleton'
import { ExchangeDialog } from '../../calendar/components/exchange-dialog'
import { isToHandle, startOfDay } from '../../calendar/dates'
import { CallTheInterlocutor } from '../../components/call-the-interlocutor'
import { CHANNEL_ICONS } from '../../components/exchange-line'
import { JoinTheVideoCall } from '../../components/join-the-video-call'
import { ToAttachBadge } from '../../components/to-attach-badge'
import { useMarkExchangeMissed, useUndoable } from '../../mutations'
import { useNow } from '../../use-now'
import { isInProgress } from '../moments'
import { type DayExchange, myDayQuery } from '../queries'

type Opened = { item: DayExchange; startWith?: 'report' }

type Props = {
  /** Who a contact is, a short version of their card, in the opened exchange. The page that composes the contacts gives it. */
  contactSummary: (contactId: string) => ReactNode
}

/**
 * My day on the dashboard: what I planned today, with the exchange in progress set apart and those whose hour has
 * passed to handle. Then the next five upcoming exchanges. An exchange opens in the calendar's dialog.
 */
export function MyDayCards({ contactSummary }: Props) {
  const now = useNow()
  const { data: myDay } = useSuspenseQuery(myDayQuery(startOfDay(now)))
  const [opened, setOpened] = useState<Opened | null>(null)

  return (
    <>
      <TodayCard
        items={myDay.today}
        now={now}
        mayPlan={Boolean(myDay._links.searchContacts)}
        onOpen={(item, startWith) => setOpened({ item, startWith })}
      />
      <UpcomingCard items={myDay.next} now={now} onOpen={(item) => setOpened({ item })} />
      <ExchangeDialog
        item={opened?.item ?? null}
        startWith={opened?.startWith}
        onClose={() => setOpened(null)}
        contactSummary={contactSummary}
      />
    </>
  )
}

const CARDS = ['today', 'upcoming'] as const

/** The two cards while my day is being read. The rest of the dashboard already shows. */
export function MyDaySkeleton() {
  return CARDS.map((card) => (
    <Card key={card}>
      <CardHeader>
        <Skeleton className="h-5 w-24" />
      </CardHeader>
      <CardContent className="space-y-2">
        <Skeleton className="h-16 w-full" />
        <Skeleton className="h-16 w-full" />
      </CardContent>
    </Card>
  ))
}

type CardProps = {
  items: DayExchange[]
  now: Date
  onOpen: (item: DayExchange, startWith?: 'report') => void
}

function TodayCard({ items, now, mayPlan, onOpen }: CardProps & { mayPlan: boolean }) {
  const { t } = useTranslation()

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{t('myDay.today')}</CardTitle>
      </CardHeader>
      <CardContent>
        {items.length === 0 ? (
          <div className="flex flex-col items-start gap-3">
            <p className="text-muted-foreground text-sm">{t('myDay.nothingToday')}</p>
            {mayPlan && (
              <Button variant="outline" size="sm" asChild>
                <Link to="/calendar" search={{ view: 'day' }}>
                  <CalendarPlus aria-hidden /> {t('myDay.plan')}
                </Link>
              </Button>
            )}
          </div>
        ) : (
          <ul className="space-y-2">
            {items.map((item) => (
              <li key={item.exchange.id}>
                <TodayExchange item={item} now={now} onOpen={onOpen} />
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  )
}

/** The exchange's hour, its channel, who it is with and when it starts. It offers its gestures at hand: join, call, done, missed. */
function TodayExchange({ item, now, onOpen }: { item: DayExchange; now: Date; onOpen: CardProps['onOpen'] }) {
  const { t } = useTranslation()
  const { exchange } = item
  const inProgress = isInProgress(exchange, now)
  const toHandle = isToHandle(exchange, now) && !inProgress

  return (
    <div
      data-in-progress={inProgress ? '' : undefined}
      className={cn('space-y-2 rounded-lg border p-3', inProgress && 'border-primary bg-primary/5')}
    >
      <ExchangeSummary item={item} when={formatMoment(exchange.occurredAt, now)} onOpen={() => onOpen(item)}>
        {inProgress && <Badge>{t('myDay.inProgress')}</Badge>}
        {toHandle && <Badge className="bg-warning text-foreground">{t('calendar.toHandle')}</Badge>}
        {!inProgress && !toHandle && (
          <span className="text-muted-foreground text-xs">{formatTimeLeft(exchange.occurredAt, now)}</span>
        )}
      </ExchangeSummary>
      <div className="flex flex-wrap gap-2 empty:hidden">
        <JoinTheVideoCall exchange={exchange} />
        {exchange.channel === 'call' && !toHandle && <CallTheInterlocutor exchangeId={exchange.id} />}
        {exchange._links.markDone && (
          <Button size="sm" onClick={() => onOpen(item, 'report')}>
            <CircleCheck aria-hidden /> {t('exchanges.handling.done')}
          </Button>
        )}
        {exchange._links.markMissed && <MarkMissed exchangeId={exchange.id} />}
      </div>
    </div>
  )
}

/** Marks the exchange missed once its toast no longer lets the member take it back, as from its detail. */
function MarkMissed({ exchangeId }: { exchangeId: string }) {
  const { t } = useTranslation()
  const markMissed = useMarkExchangeMissed()
  const undoable = useUndoable()

  return (
    <Button
      variant="outline"
      size="sm"
      onClick={() =>
        undoable(t('exchanges.handling.markedMissed'), () =>
          markMissed.mutate({ params: { path: { id: exchangeId } } }),
        )
      }
    >
      <PhoneMissed aria-hidden /> {t('exchanges.handling.missed')}
    </Button>
  )
}

function UpcomingCard({ items, now, onOpen }: CardProps) {
  const { t } = useTranslation()

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{t('myDay.upcoming')}</CardTitle>
        <CardAction>
          <Button variant="ghost" size="sm" asChild>
            <Link to="/calendar">
              {t('myDay.seeTheCalendar')} <ArrowRight aria-hidden />
            </Link>
          </Button>
        </CardAction>
      </CardHeader>
      <CardContent>
        {items.length === 0 ? (
          <p className="text-muted-foreground text-sm">{t('myDay.nothingUpcoming')}</p>
        ) : (
          <ul className="space-y-2">
            {items.map((item) => (
              <li key={item.exchange.id} className="rounded-lg border p-3">
                <ExchangeSummary
                  item={item}
                  when={formatUpcoming(item.exchange.occurredAt, now)}
                  onOpen={() => onOpen(item)}
                />
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  )
}

type SummaryProps = { item: DayExchange; when: string; onOpen: () => void; children?: ReactNode }

/** When, on which channel, with whom and their organization. A click opens the exchange. */
function ExchangeSummary({ item, when, onOpen, children }: SummaryProps) {
  const { t } = useTranslation()
  const { exchange, interlocutor, organization } = item
  const ChannelIcon = CHANNEL_ICONS[exchange.channel]

  return (
    <div className="flex items-start justify-between gap-2">
      <button
        type="button"
        onClick={onOpen}
        className="min-w-0 flex-1 rounded-md text-left outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
      >
        <span className="flex items-center gap-2 text-sm">
          <span className="font-medium tabular-nums">{when}</span>
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
      {children}
    </div>
  )
}
