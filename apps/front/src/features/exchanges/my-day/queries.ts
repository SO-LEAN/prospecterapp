import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { periodOf, queryOf } from '../calendar/dates'

export type MyDay = components['schemas']['MyDayResource']
export type DayExchange = MyDay['today'][number]

type Reading = { enabled?: boolean; refetchInterval?: number }

/**
 * What I planned on a day, in the time of the browser, then the next ones: a query, and a key, per day; read again
 * every `refetchInterval` when given (the reminders).
 */
export const myDayQuery = (day: Date, reading: Reading = {}) =>
  api.queryOptions('get', '/api/me/day', { params: { query: queryOf(periodOf('day', day)) } }, reading)
