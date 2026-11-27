import { useQueryClient, useSuspenseQuery } from '@tanstack/react-query'
import { CalendarSearch, ChevronLeft, ChevronRight } from 'lucide-react'
import { type ReactNode, useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { formatDayTitle, formatMonthTitle, formatWeekTitle } from '@/shared/lib/format'
import { Button } from '@/shared/ui/button'
import { PageHeader } from '@/shared/ui/page-header'
import { Popover, PopoverContent, PopoverTrigger } from '@/shared/ui/popover'
import type { InterlocutorSearch } from '../../components/with-the-chosen'
import type { Slot } from '../../planning'
import { useNow } from '../../use-now'
import { neighbourOf, type Period, periodOf, startOfDay, VIEWS, type View } from '../dates'
import { useMoveInTheCalendar, withdrawTheOfferToMoveBack } from '../mutations'
import {
  type CalendarExchange,
  type CalendarToDo,
  calendarQuery,
  readTheDaysAroundAhead,
  readTheNeighbourAhead,
} from '../queries'
import { type Shortcut, useCalendarShortcuts } from '../shortcuts'
import { SHOWN, type Shown, shownOf } from '../to-dos'
import { AgendaView } from './agenda-view'
import { type ReviseToDo, ToDoBoxOf, toDoDrop } from './calendar-to-do'
import { ExchangeDialog } from './exchange-dialog'
import { MiniMonth } from './mini-month'
import { MonthView } from './month-view'
import { PlanInASlot } from './plan-in-a-slot'
import { TimeGridView } from './time-grid-view'

/** "Novembre 2026", "9 au 15 nov. 2026 · S46", "Mardi 17 novembre 2026" */
function titleOf(view: View, day: Date, period: Period): string {
  switch (view) {
    case 'month':
    case 'agenda':
      return formatMonthTitle(day)
    case 'week':
      return formatWeekTitle(period.first, new Date(period.end.getTime() - 1))
    case 'day':
      return formatDayTitle(day)
  }
}

type Props = {
  view: View
  day: Date
  onShow: (view: View, day: Date) => void
  /** Who a contact is, a short version of their card, in the opened exchange. The page that composes the contacts gives it. */
  contactSummary: (contactId: string) => ReactNode
  /** The search for who to plan with in a free slot. The page that composes the contacts and organizations gives it. */
  interlocutorSearch: InterlocutorSearch
  /** The box that ticks a to-do off or unticks it. The page that composes the to-dos gives it. */
  toDoBox: (toDo: CalendarToDo) => ReactNode
  /** Revises a to-do dropped on another day. The page that composes the to-dos gives it. */
  reviseToDo: ReviseToDo
}

/**
 * My calendar. The shown view and day come from the URL, and each change goes back to it. Only the shown period is
 * read. The next and the previous ones are read when the member hovers their arrow, and the days around a day are
 * read ahead. It shows my exchanges and my to-dos, or one of them, and flags what is late. An exchange opens in a
 * dialog. An upcoming exchange can be dragged, and a to-do can be dragged to another day. A free slot can be planned
 * when the API offers the search for contacts to plan with.
 */
export function CalendarPage({
  view,
  day,
  onShow,
  contactSummary,
  interlocutorSearch,
  toDoBox,
  reviseToDo,
}: Props) {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const now = useNow()
  const period = periodOf(view, day)
  const { data } = useSuspenseQuery(calendarQuery(period))
  const [jumping, setJumping] = useState(false)
  const [opened, setOpened] = useState<CalendarExchange | null>(null)
  const [slot, setSlot] = useState<Slot | null>(null)
  const [shown, setShown] = useState<Shown>('both')
  const { items, toDos } = shownOf(data, shown)
  const late = new Set(data.late)
  const dropOn = toDoDrop(data.toDos, reviseToDo)
  const move = useMoveInTheCalendar()
  const open = (item: CalendarExchange) => {
    withdrawTheOfferToMoveBack()
    setOpened(item)
  }
  const gestures = { onOpen: open, onMove: move.mutate }

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
    <ToDoBoxOf value={toDoBox}>
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
              <Button variant="ghost" size="sm" className="font-semibold text-base">
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
            <legend className="sr-only">{t('calendar.shown.label')}</legend>
            {SHOWN.map((filter) => (
              <Button
                key={filter}
                variant={filter === shown ? 'secondary' : 'ghost'}
                size="sm"
                aria-pressed={filter === shown}
                onClick={() => setShown(filter)}
              >
                {t(`calendar.shown.${filter}`)}
              </Button>
            ))}
          </fieldset>
          <fieldset className="flex rounded-lg border p-0.5">
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
          <MonthView
            period={period}
            month={day.getMonth()}
            items={items}
            toDos={toDos}
            late={late}
            dropOn={dropOn}
            now={now}
            onOpenDay={openDay}
            {...gestures}
          />
        )}
        {(view === 'week' || view === 'day') && (
          <TimeGridView
            key={view}
            period={period}
            items={items}
            toDos={toDos}
            late={late}
            dropOn={dropOn}
            now={now}
            onOpenDay={view === 'week' ? openDay : undefined}
            onChooseSlot={data._links.searchContacts ? setSlot : undefined}
            {...gestures}
          />
        )}
        {view === 'agenda' && <AgendaView items={items} toDos={toDos} late={late} now={now} onOpen={open} />}
        <ExchangeDialog item={opened} onClose={() => setOpened(null)} contactSummary={contactSummary} />
        <PlanInASlot slot={slot} onClose={() => setSlot(null)} interlocutorSearch={interlocutorSearch} />

        <p className="hidden text-muted-foreground text-xs md:block">{t('calendar.shortcuts')}</p>
      </div>
    </ToDoBoxOf>
  )
}
