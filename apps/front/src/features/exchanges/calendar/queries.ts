import type { QueryClient } from '@tanstack/react-query'
import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { readerTimeZone } from '@/shared/lib/time-zone'
import { addDays, neighbourOf, type Period, periodOf, queryOf, type View } from './dates'

export type Calendar = components['schemas']['CalendarResource']
export type CalendarExchange = Calendar['items'][number]
export type CalendarToDo = Calendar['toDos'][number]

/** In the day view, the days around the shown one are read ahead. Going to the next day is instant. */
const DAYS_READ_AHEAD = 3

/**
 * The exchanges I hold in a period, whatever their status, by their moment, and my to-dos due in it, done or not. It
 * also says what is late on my clock. There is one query, and one key, per period.
 */
export const calendarQuery = (period: Period) =>
  api.queryOptions('get', '/api/me/calendar', {
    params: { query: { ...queryOf(period), timeZone: readerTimeZone() } },
  })

/** Reads the period before or after the shown one before the member asks for it, when they hover its arrow. */
export function readTheNeighbourAhead(queryClient: QueryClient, view: View, day: Date, step: 1 | -1) {
  void queryClient.prefetchQuery(calendarQuery(periodOf(view, neighbourOf(view, day, step))))
}

/** In the day view, reads the three days before and after it ahead. */
export function readTheDaysAroundAhead(queryClient: QueryClient, day: Date) {
  for (let distance = 1; distance <= DAYS_READ_AHEAD; distance++) {
    void queryClient.prefetchQuery(calendarQuery(periodOf('day', addDays(day, distance))))
    void queryClient.prefetchQuery(calendarQuery(periodOf('day', addDays(day, -distance))))
  }
}
