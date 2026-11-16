import type { QueryClient } from '@tanstack/react-query'
import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { readerTimeZone } from '@/shared/lib/time-zone'
import { addDays, neighbourOf, type Period, periodOf, queryOf, type View } from './dates'

export type Calendar = components['schemas']['CalendarResource']
export type CalendarExchange = Calendar['items'][number]
export type CalendarToDo = Calendar['toDos'][number]

/** The days around the one shown, read ahead in the view of a day: going to the next is instant. */
const DAYS_READ_AHEAD = 3

/**
 * The exchanges I hold in a period, whatever their status, by their moment, and my to-dos due in it, done or not; what
 * is late on my clock: a query, and a key, per period.
 */
export const calendarQuery = (period: Period) =>
  api.queryOptions('get', '/api/me/calendar', {
    params: { query: { ...queryOf(period), timeZone: readerTimeZone() } },
  })

/** The period before or after the one shown, read before it is asked for: on the hover of its arrow. */
export function readTheNeighbourAhead(queryClient: QueryClient, view: View, day: Date, step: 1 | -1) {
  void queryClient.prefetchQuery(calendarQuery(periodOf(view, neighbourOf(view, day, step))))
}

/** In the view of a day, the three days before and after it. */
export function readTheDaysAroundAhead(queryClient: QueryClient, day: Date) {
  for (let distance = 1; distance <= DAYS_READ_AHEAD; distance++) {
    void queryClient.prefetchQuery(calendarQuery(periodOf('day', addDays(day, distance))))
    void queryClient.prefetchQuery(calendarQuery(periodOf('day', addDays(day, -distance))))
  }
}
