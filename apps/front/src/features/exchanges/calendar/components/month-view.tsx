import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { byDay, dayOf, daysOf, isSameDay, type Period } from '../dates'
import { DAY_ATTRIBUTE } from '../gestures'
import type { CalendarMove } from '../mutations'
import type { CalendarExchange, CalendarToDo } from '../queries'
import { toDosByDay } from '../to-dos'
import { useGestures } from '../use-gestures'
import { CalendarExchangeChip } from './calendar-exchange'
import { CalendarToDoChip, type toDoDrop } from './calendar-to-do'

/** Beyond them, a day of the month tells how many more it holds, and opens on its own. */
export const SHOWN_IN_A_DAY_OF_THE_MONTH = 3

const weekdayFormat = new Intl.DateTimeFormat('fr-FR', { weekday: 'short' })
const longDayFormat = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' })

type Props = {
  period: Period
  month: number
  items: CalendarExchange[]
  toDos: CalendarToDo[]
  /** The ids of the exchanges and of the to-dos late, as the API says. */
  late: ReadonlySet<string>
  /** Where a to-do is dropped to change its day. */
  dropOn: ReturnType<typeof toDoDrop>
  now: Date
  onOpenDay: (day: Date) => void
  onOpen: (item: CalendarExchange) => void
  onMove: (move: CalendarMove) => void
}

/**
 * The weeks of a month, each day with its first to-dos and exchanges and how many more; the days of the months around
 * it faded, today marked. An exchange the API lets move is dragged to another day, at its own hour, a to-do too.
 */
export function MonthView({
  period,
  month,
  items,
  toDos,
  late,
  dropOn,
  now,
  onOpenDay,
  onOpen,
  onMove,
}: Props) {
  const { t } = useTranslation()
  const days = daysOf(period)
  const gestures = useGestures({ items, onMove })
  const exchangesOf = byDay(gestures.shown)
  const toDosOf = toDosByDay(toDos)

  return (
    <div className="overflow-hidden rounded-xl border bg-card">
      <div className="grid grid-cols-7 border-b bg-muted/40" aria-hidden>
        {days.slice(0, 7).map((day) => (
          <div
            key={dayOf(day)}
            className="px-2 py-1.5 text-center font-medium text-muted-foreground text-xs capitalize"
          >
            {weekdayFormat.format(day)}
          </div>
        ))}
      </div>
      <ol className="grid grid-cols-7">
        {days.map((day) => {
          const ofTheDay = exchangesOf.get(dayOf(day)) ?? []
          const toDosOfTheDay = (toDosOf.get(dayOf(day)) ?? []).slice(0, SHOWN_IN_A_DAY_OF_THE_MONTH)
          const exchangesShown = SHOWN_IN_A_DAY_OF_THE_MONTH - toDosOfTheDay.length
          const hidden =
            (toDosOf.get(dayOf(day))?.length ?? 0) + ofTheDay.length - SHOWN_IN_A_DAY_OF_THE_MONTH
          const today = isSameDay(day, now)
          return (
            <li
              key={dayOf(day)}
              aria-label={longDayFormat.format(day)}
              {...{ [DAY_ATTRIBUTE]: dayOf(day) }}
              {...dropOn(dayOf(day))}
              className={cn(
                'flex min-h-24 min-w-0 flex-col gap-0.5 border-r border-b p-1 [&:nth-child(7n)]:border-r-0',
                day.getMonth() !== month && 'bg-muted/30 text-muted-foreground',
              )}
            >
              <button
                type="button"
                onClick={() => onOpenDay(day)}
                aria-label={longDayFormat.format(day)}
                className={cn(
                  'grid size-6 place-items-center self-end rounded-full text-xs tabular-nums hover:bg-accent',
                  today && 'bg-primary font-semibold text-primary-foreground hover:bg-primary/90',
                )}
              >
                {day.getDate()}
              </button>
              {toDosOfTheDay.map((toDo) => (
                <CalendarToDoChip key={toDo.id} toDo={toDo} late={late.has(toDo.id)} />
              ))}
              {ofTheDay.slice(0, exchangesShown).map((item) => (
                <CalendarExchangeChip
                  key={item.exchange.id}
                  item={item}
                  now={now}
                  late={late.has(item.exchange.id)}
                  movable
                  dragged={gestures.draggedId === item.exchange.id}
                  onClick={() => gestures.unlessDraggedJustNow(() => onOpen(item))}
                  onPointerDown={gestures.grab(item, 'move')}
                />
              ))}
              {hidden > 0 && (
                <button
                  type="button"
                  onClick={() => onOpenDay(day)}
                  aria-label={t('calendar.moreOn', { count: hidden, day: longDayFormat.format(day) })}
                  className="self-start rounded px-1.5 font-medium text-muted-foreground text-xs hover:bg-accent hover:text-foreground"
                >
                  {t('calendar.more', { count: hidden })}
                </button>
              )}
            </li>
          )
        })}
      </ol>
    </div>
  )
}
