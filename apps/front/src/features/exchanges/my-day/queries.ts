import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { periodOf, queryOf } from '../calendar/dates'

export type MyDay = components['schemas']['MyDayResource']
export type DayExchange = MyDay['today'][number]

/** What I planned on a day, in the time of the browser, then the next ones: a query, and a key, per day. */
export const myDayQuery = (day: Date) =>
  api.queryOptions('get', '/api/me/day', { params: { query: queryOf(periodOf('day', day)) } })

/**
 * My day as the reminders follow it, reading what the pages read without reading it with them: the reminders read
 * it on their own moments (`useMyDayToRemind`), never among the reads a gesture asks at once.
 */
export const myDayToRemindQuery = (day: Date) =>
  api.queryOptions(
    'get',
    '/api/me/day',
    { params: { query: queryOf(periodOf('day', day)) } },
    { enabled: false, staleTime: 0 },
  )
