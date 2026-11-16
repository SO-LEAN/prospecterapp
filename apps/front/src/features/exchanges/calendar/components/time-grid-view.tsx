import { type CSSProperties, useEffect, useRef } from 'react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import type { Slot } from '../../planning'
import { byDay, dayOf, daysOf, isSameDay, type Period } from '../dates'
import { DAY_ATTRIBUTE } from '../gestures'
import type { CalendarMove } from '../mutations'
import type { CalendarExchange, CalendarToDo } from '../queries'
import { hoursFromMidnight, placeInTheDay } from '../time-grid'
import { hoursOfDue, toDosByDay } from '../to-dos'
import { useGestures } from '../use-gestures'
import { CalendarExchangeChip } from './calendar-exchange'
import { CalendarToDoChip, type toDoDrop } from './calendar-to-do'

const HOURS = Array.from({ length: 24 }, (_, hour) => hour)
const MINUTES_IN_AN_HOUR = 60
/** The working day the grid opens on, the other hours a scroll away. */
const FIRST_HOUR_SHOWN = 8
const dayFormat = new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric' })
const longDayFormat = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' })

/**
 * Where something stands in the grid is data, not a style: it is passed to the classes of the grid as custom
 * properties, in hours from midnight and in lanes.
 */
const placed = (properties: Record<`--${string}`, number>) => properties as CSSProperties

type Props = {
  period: Period
  items: CalendarExchange[]
  toDos: CalendarToDo[]
  /** The ids of the exchanges and of the to-dos late, as the API says. */
  late: ReadonlySet<string>
  /** Where a to-do is dropped to change its day. */
  dropOn: ReturnType<typeof toDoDrop>
  now: Date
  onOpenDay?: (day: Date) => void
  onOpen: (item: CalendarExchange) => void
  onMove: (move: CalendarMove) => void
  /** Absent when the member may not plan: no free slot is chosen. */
  onChooseSlot?: (slot: Slot) => void
}

/**
 * The hours of a week or of a day, each exchange at its moment for its duration, those overlapping side by side; the
 * to-dos of a day without an hour in a band above them, those with an hour at it, without a duration (ADR 0134); the
 * line of now on today. It opens on the working hours, the others a scroll away. An exchange the API lets move is
 * dragged to another moment or stretched by its end, a to-do to another day; a free slot clicked, or dragged for its
 * duration, is planned when the member may plan.
 */
export function TimeGridView({
  period,
  items,
  toDos,
  late,
  dropOn,
  now,
  onOpenDay,
  onOpen,
  onMove,
  onChooseSlot,
}: Props) {
  const { t } = useTranslation()
  const days = daysOf(period)
  const gestures = useGestures({ items, onMove, onChooseSlot })
  const exchangesOf = byDay(gestures.shown)
  const toDosOf = toDosByDay(toDos)
  const scrolled = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const grid = scrolled.current
    if (grid) grid.scrollTop = (grid.scrollHeight * FIRST_HOUR_SHOWN) / HOURS.length
  }, [])

  return (
    <div className="overflow-hidden rounded-xl border bg-card [--hour:3rem]">
      <div className="flex border-b bg-muted/40">
        <div className="w-12 shrink-0" aria-hidden />
        {days.map((day) => (
          <div key={dayOf(day)} className="min-w-0 flex-1 border-l px-1 py-1.5 text-center">
            <button
              type="button"
              disabled={!onOpenDay}
              onClick={() => onOpenDay?.(day)}
              aria-label={longDayFormat.format(day)}
              className={cn(
                'rounded-md px-1.5 font-medium text-muted-foreground text-xs capitalize enabled:hover:bg-accent',
                isSameDay(day, now) && 'text-primary',
              )}
            >
              {dayFormat.format(day)}
            </button>
          </div>
        ))}
      </div>
      <div className="flex border-b">
        <div className="w-12 shrink-0 py-1 pr-1 text-right text-[11px] text-muted-foreground">
          {t('calendar.allDay')}
        </div>
        {days.map((day) => (
          <ul
            key={dayOf(day)}
            aria-label={t('calendar.allDayOf', { day: longDayFormat.format(day) })}
            {...dropOn(dayOf(day))}
            className="min-h-7 min-w-0 flex-1 space-y-0.5 border-l p-0.5"
          >
            {(toDosOf.get(dayOf(day)) ?? [])
              .filter((toDo) => !toDo.dueAt)
              .map((toDo) => (
                <li key={toDo.id}>
                  <CalendarToDoChip toDo={toDo} late={late.has(toDo.id)} />
                </li>
              ))}
          </ul>
        ))}
      </div>
      <div ref={scrolled} className="h-[calc(11*var(--hour))] overflow-y-auto">
        <div className="relative flex h-[calc(24*var(--hour))]">
          <ol className="w-12 shrink-0" aria-label={t('calendar.hours')}>
            {HOURS.map((hour) => (
              <li
                key={hour}
                className="h-(--hour) pr-1 text-right text-[11px] text-muted-foreground tabular-nums"
              >
                {hour > 0 && t('calendar.hour', { hour })}
              </li>
            ))}
          </ol>
          {days.map((day) => (
            <ol
              key={dayOf(day)}
              aria-label={longDayFormat.format(day)}
              {...{ [DAY_ATTRIBUTE]: dayOf(day) }}
              data-hours=""
              {...gestures.slotHandlers(day)}
              {...dropOn(dayOf(day))}
              className={cn(
                'relative min-w-0 flex-1 border-l bg-[repeating-linear-gradient(to_bottom,transparent_0_calc(var(--hour)-1px),var(--color-border)_calc(var(--hour)-1px)_var(--hour))]',
                onChooseSlot && 'cursor-cell',
              )}
            >
              {placeInTheDay(exchangesOf.get(dayOf(day)) ?? []).map(
                ({ item, start, length, lane, lanes }) => (
                  <li
                    key={item.exchange.id}
                    style={placed({ '--start': start, '--length': length, '--lane': lane, '--lanes': lanes })}
                    className="absolute top-[calc(var(--start)*var(--hour))] left-[calc(var(--lane)*100%/var(--lanes))] h-[calc(var(--length)*var(--hour))] w-[calc(100%/var(--lanes))] p-px"
                  >
                    <CalendarExchangeChip
                      item={item}
                      now={now}
                      late={late.has(item.exchange.id)}
                      movable
                      dragged={gestures.draggedId === item.exchange.id}
                      className="h-full"
                      onClick={() => gestures.unlessDraggedJustNow(() => onOpen(item))}
                      onPointerDown={gestures.grab(item, 'move')}
                    >
                      {item.exchange._links.move && (
                        <span
                          aria-hidden
                          data-stretch=""
                          onPointerDown={gestures.grab(item, 'stretch')}
                          className="absolute inset-x-0 bottom-0 h-1.5 cursor-ns-resize"
                        />
                      )}
                    </CalendarExchangeChip>
                  </li>
                ),
              )}
              {(toDosOf.get(dayOf(day)) ?? []).flatMap((toDo) =>
                toDo.dueAt
                  ? [
                      <li
                        key={toDo.id}
                        style={placed({ '--start': hoursOfDue(toDo.dueAt) })}
                        className="absolute inset-x-0 top-[calc(var(--start)*var(--hour))] z-10 p-px"
                      >
                        <CalendarToDoChip toDo={toDo} late={late.has(toDo.id)} className="shadow-sm" />
                      </li>,
                    ]
                  : [],
              )}
              {gestures.choosing && gestures.choosingDay && isSameDay(gestures.choosingDay, day) && (
                <li
                  aria-hidden
                  style={placed({
                    '--start': hoursFromMidnight(gestures.choosing.startsAt),
                    '--length': gestures.choosing.durationInMinutes / MINUTES_IN_AN_HOUR,
                  })}
                  className="pointer-events-none absolute inset-x-0 top-[calc(var(--start)*var(--hour))] h-[calc(var(--length)*var(--hour))] rounded-md border-2 border-primary border-dashed bg-primary/10"
                />
              )}
              {isSameDay(day, now) && (
                <li
                  aria-label={t('calendar.now')}
                  style={placed({ '--now': hoursFromMidnight(now) })}
                  className="pointer-events-none absolute inset-x-0 top-[calc(var(--now)*var(--hour))] z-10 h-0.5 bg-destructive before:absolute before:-top-1 before:-left-1 before:size-2.5 before:rounded-full before:bg-destructive"
                />
              )}
            </ol>
          ))}
        </div>
      </div>
    </div>
  )
}
