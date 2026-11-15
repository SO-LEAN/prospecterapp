import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { periodOf, queryOf } from '../calendar/dates'

export type MyDay = components['schemas']['MyDayResource']
export type DayExchange = MyDay['today'][number]

/** What I planned on a day, in the time of the browser, then the next ones: a query, and a key, per day. */
export const myDayQuery = (day: Date) =>
  api.queryOptions('get', '/api/me/day', { params: { query: queryOf(periodOf('day', day)) } })
