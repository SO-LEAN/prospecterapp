import { type CSSProperties, useEffect, useRef } from 'react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { byDay, dayOf, daysOf, isSameDay, type Period } from '../dates'
import type { CalendarExchange } from '../queries'
import { hoursFromMidnight, placeInTheDay } from '../time-grid'
import { CalendarExchangeChip } from './calendar-exchange'

const HOURS = Array.from({ length: 24 }, (_, hour) => hour)
/** The working day the grid opens on, the other hours a scroll away. */
const FIRST_HOUR_SHOWN = 8

const dayFormat = new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric' })
const longDayFormat = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' })

/**
 * Where something stands in the grid is data, not a style: it is passed to the classes of the grid as custom
 * properties, in hours from midnight and in lanes.
 */
const placed = (properties: Record<`--${string}`, number>) => properties as CSSProperties

/**
 * The hours of a week or of a day, each exchange at its moment for its duration, those overlapping side by side; the
 * line of now on today. It opens on the working hours, the others a scroll away.
 */
export function TimeGridView({
  period,
  items,
  now,
  onOpenDay,
}: {
  period: Period
  items: CalendarExchange[]
  now: Date
  onOpenDay?: (day: Date) => void
}) {
  const { t } = useTranslation()
  const days = daysOf(period)
  const exchangesOf = byDay(items)
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
              className="relative min-w-0 flex-1 border-l bg-[repeating-linear-gradient(to_bottom,transparent_0_calc(var(--hour)-1px),var(--color-border)_calc(var(--hour)-1px)_var(--hour))]"
            >
              {placeInTheDay(exchangesOf.get(dayOf(day)) ?? []).map(
                ({ item, start, length, lane, lanes }) => (
                  <li
                    key={item.exchange.id}
                    style={placed({ '--start': start, '--length': length, '--lane': lane, '--lanes': lanes })}
                    className="absolute top-[calc(var(--start)*var(--hour))] left-[calc(var(--lane)*100%/var(--lanes))] h-[calc(var(--length)*var(--hour))] w-[calc(100%/var(--lanes))] p-px"
                  >
                    <CalendarExchangeChip item={item} now={now} className="h-full" />
                  </li>
                ),
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
