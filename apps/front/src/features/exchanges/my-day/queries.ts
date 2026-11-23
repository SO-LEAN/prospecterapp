import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { periodOf, queryOf } from '../calendar/dates'

export type MyDay = components['schemas']['MyDayResource']
export type DayExchange = MyDay['today'][number]

/** What I planned on a day, in the browser's time zone, then the next exchanges. There is one query, and one key, per day. */
export const myDayQuery = (day: Date) =>
  api.queryOptions('get', '/api/me/day', { params: { query: queryOf(periodOf('day', day)) } })

/**
 * My day as the reminders follow it. It reads what the pages read, but not at the same time. The reminders read it at
 * their own moments (`useMyDayToRemind`), never among the reads that a gesture asks for at once.
 */
export const myDayToRemindQuery = (day: Date) =>
  api.queryOptions(
    'get',
    '/api/me/day',
    { params: { query: queryOf(periodOf('day', day)) } },
    { enabled: false, staleTime: 0 },
  )
