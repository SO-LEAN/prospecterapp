import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { periodOf, queryOf } from '../calendar/dates'

export type MyDay = components['schemas']['MyDayResource']
export type DayExchange = MyDay['today'][number]

/** My day is read again that often for the reminders, besides after each gesture on one of its exchanges. */
const READ_AGAIN_FOR_THE_REMINDERS_MS = 5 * 60_000

/** What I planned on a day, in the time of the browser, then the next ones: a query, and a key, per day. */
export const myDayQuery = (day: Date) =>
  api.queryOptions('get', '/api/me/day', { params: { query: queryOf(periodOf('day', day)) } })

/** My day as the reminders read it: again every five minutes, in a tab in the background too, where they notify. */
export const myDayToRemindQuery = (day: Date) =>
  api.queryOptions(
    'get',
    '/api/me/day',
    { params: { query: queryOf(periodOf('day', day)) } },
    { refetchInterval: READ_AGAIN_FOR_THE_REMINDERS_MS, refetchIntervalInBackground: true },
  )
