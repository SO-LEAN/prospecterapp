import { useQueryClient, useSuspenseQuery } from '@tanstack/react-query'
import { CalendarSearch, ChevronLeft, ChevronRight } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { PageHeader } from '@/shared/ui/page-header'
import { Popover, PopoverContent, PopoverTrigger } from '@/shared/ui/popover'
import { neighbourOf, type Period, periodOf, startOfDay, VIEWS, type View } from '../dates'
import { calendarQuery, readTheDaysAroundAhead, readTheNeighbourAhead } from '../queries'
import { type Shortcut, useCalendarShortcuts } from '../shortcuts'
import { AgendaView } from './agenda-view'
import { MiniMonth } from './mini-month'
import { MonthView } from './month-view'
import { TimeGridView } from './time-grid-view'

const MINUTE_MS = 60_000

const monthFormat = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' })
const dayFormat = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' })
const rangeFormat = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })

/** "novembre 2026", "9 – 15 nov. 2026", "mardi 17 novembre 2026" */
function titleOf(view: View, day: Date, period: Period): string {
  switch (view) {
    case 'month':
    case 'agenda':
      return monthFormat.format(day)
    case 'week':
      return rangeFormat.formatRange(period.first, new Date(period.end.getTime() - 1))
    case 'day':
      return dayFormat.format(day)
  }
}

/** The moment of the screen, again each minute: the line of now moves, what passed becomes to handle. */
function useNow(): Date {
  const [now, setNow] = useState(() => new Date())
  useEffect(() => {
    const ticking = window.setInterval(() => setNow(new Date()), MINUTE_MS)
    return () => window.clearInterval(ticking)
  }, [])
  return now
}

/**
 * My calendar: the view and the day shown come from the URL, each change goes back to it; the period shown is read
 * alone, the next and the previous ones read on the hover of their arrow, the days around a day read ahead.
 */
export function CalendarPage({
  view,
  day,
  onShow,
}: {
  view: View
  day: Date
  onShow: (view: View, day: Date) => void
}) {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const now = useNow()
  const period = periodOf(view, day)
  const { data } = useSuspenseQuery(calendarQuery(period))
  const [jumping, setJumping] = useState(false)

  useEffect(() => {
    if (view === 'day') readTheDaysAroundAhead(queryClient, day)
  }, [view, day, queryClient])

  useCalendarShortcuts((shortcut: Shortcut) => {
    if (shortcut.kind === 'today') onShow(view, startOfDay(new Date()))
    if (shortcut.kind === 'step') onShow(view, neighbourOf(view, day, shortcut.step))
    if (shortcut.kind === 'view') onShow(shortcut.view, day)
  })

  const openDay = (shown: Date) => onShow('day', shown)
  const arrow = (step: 1 | -1) => ({
    onClick: () => onShow(view, neighbourOf(view, day, step)),
    onPointerEnter: () => readTheNeighbourAhead(queryClient, view, day, step),
    onFocus: () => readTheNeighbourAhead(queryClient, view, day, step),
  })

  return (
    <div className="space-y-4">
      <PageHeader title={t('calendar.title')} description={t('calendar.subtitle')} />

      <div className="flex flex-wrap items-center gap-2">
        <Button variant="outline" size="sm" onClick={() => onShow(view, startOfDay(new Date()))}>
          {t('calendar.today')}
        </Button>
        <div className="flex">
          <Button variant="ghost" size="icon-sm" {...arrow(-1)}>
            <ChevronLeft aria-hidden />
            <span className="sr-only">{t('calendar.previous')}</span>
          </Button>
          <Button variant="ghost" size="icon-sm" {...arrow(1)}>
            <ChevronRight aria-hidden />
            <span className="sr-only">{t('calendar.next')}</span>
          </Button>
        </div>
        <Popover open={jumping} onOpenChange={setJumping}>
          <PopoverTrigger asChild>
            <Button variant="ghost" size="sm" className="font-semibold text-base capitalize">
              {titleOf(view, day, period)}
              <CalendarSearch aria-hidden className="text-muted-foreground" />
              <span className="sr-only">{t('calendar.jumpTo')}</span>
            </Button>
          </PopoverTrigger>
          <PopoverContent align="start" className="w-auto p-3">
            <MiniMonth
              selected={day}
              today={now}
              onPick={(picked) => {
                setJumping(false)
                onShow(view, picked)
              }}
            />
          </PopoverContent>
        </Popover>
        <fieldset className="ml-auto flex rounded-lg border p-0.5">
          <legend className="sr-only">{t('calendar.view')}</legend>
          {VIEWS.map((shown) => (
            <Button
              key={shown}
              variant={shown === view ? 'secondary' : 'ghost'}
              size="sm"
              aria-pressed={shown === view}
              onClick={() => onShow(shown, day)}
            >
              {t(`calendar.views.${shown}`)}
            </Button>
          ))}
        </fieldset>
      </div>

      {view === 'month' && (
        <MonthView period={period} month={day.getMonth()} items={data.items} now={now} onOpenDay={openDay} />
      )}
      {view === 'week' && <TimeGridView period={period} items={data.items} now={now} onOpenDay={openDay} />}
      {view === 'day' && <TimeGridView period={period} items={data.items} now={now} />}
      {view === 'agenda' && <AgendaView items={data.items} now={now} />}

      <p className="hidden text-muted-foreground text-xs md:block">{t('calendar.shortcuts')}</p>
    </div>
  )
}
