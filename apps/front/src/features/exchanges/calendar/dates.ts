import type { Exchange } from '../queries'

export const VIEWS = ['month', 'week', 'day', 'agenda'] as const
export type View = (typeof VIEWS)[number]

/** The days the screen shows, in the time of the browser: from midnight of the first to midnight after the last. */
export type Period = { first: Date; end: Date }

const DAYS_IN_A_WEEK = 7
const MONDAY = 1
const DAY_WRITTEN = /^(\d{4})-(\d{2})-(\d{2})$/

/** "2026-11-17" -> midnight of that day in the time of the browser */
export function parseDay(written: string): Date {
  const [, year, month, date] = DAY_WRITTEN.exec(written) ?? []

  return year ? new Date(Number(year), Number(month) - 1, Number(date)) : startOfDay(new Date())
}

/** Midnight of a day -> "2026-11-17" */
export function dayOf(moment: Date): string {
  return `${moment.getFullYear()}-${twoDigits(moment.getMonth() + 1)}-${twoDigits(moment.getDate())}`
}

export function isDay(written: string): boolean {
  return DAY_WRITTEN.test(written)
}

export function startOfDay(moment: Date): Date {
  return new Date(moment.getFullYear(), moment.getMonth(), moment.getDate())
}

/** By the calendar, not by 24 hours: midnight stays midnight across a change of time. */
export function addDays(day: Date, days: number): Date {
  return new Date(day.getFullYear(), day.getMonth(), day.getDate() + days)
}

export function isSameDay(one: Date, other: Date): boolean {
  return dayOf(one) === dayOf(other)
}

const mondayOf = (day: Date) => addDays(day, -((day.getDay() - MONDAY + DAYS_IN_A_WEEK) % DAYS_IN_A_WEEK))
const firstOfTheMonth = (day: Date) => new Date(day.getFullYear(), day.getMonth(), 1)
const firstOfTheNextMonth = (day: Date) => new Date(day.getFullYear(), day.getMonth() + 1, 1)

/**
 * A month is shown in whole weeks, from the Monday of its first to the Sunday of its last: six weeks at most, what
 * the API reads at once; the agenda lists its days only.
 */
export function periodOf(view: View, day: Date): Period {
  switch (view) {
    case 'month':
      return {
        first: mondayOf(firstOfTheMonth(day)),
        end: mondayOf(addDays(firstOfTheNextMonth(day), DAYS_IN_A_WEEK - 1)),
      }
    case 'week':
      return { first: mondayOf(day), end: addDays(mondayOf(day), DAYS_IN_A_WEEK) }
    case 'day':
      return { first: startOfDay(day), end: addDays(day, 1) }
    case 'agenda':
      return { first: firstOfTheMonth(day), end: firstOfTheNextMonth(day) }
  }
}

export function daysOf({ first, end }: Period): Date[] {
  const days: Date[] = []
  for (let day = first; day < end; day = addDays(day, 1)) days.push(day)
  return days
}

/** The day the previous or the next period opens on: a month opens on its first day. */
export function neighbourOf(view: View, day: Date, step: 1 | -1): Date {
  switch (view) {
    case 'month':
    case 'agenda':
      return new Date(day.getFullYear(), day.getMonth() + step, 1)
    case 'week':
      return addDays(day, step * DAYS_IN_A_WEEK)
    case 'day':
      return addDays(day, step)
  }
}

/** What the API reads: the bounds of the period, each at the offset of the browser then, the end excluded. */
export function queryOf({ first, end }: Period): { from: string; to: string } {
  return { from: momentWithItsOffset(first), to: momentWithItsOffset(end) }
}

/** 9:05 on 17 November 2026 in Paris -> "2026-11-17T09:05:00+01:00" */
export function momentWithItsOffset(moment: Date): string {
  const offset = -moment.getTimezoneOffset()
  const sign = offset < 0 ? '-' : '+'
  const hours = twoDigits(Math.floor(Math.abs(offset) / 60))
  const minutes = twoDigits(Math.abs(offset) % 60)
  const time = `${twoDigits(moment.getHours())}:${twoDigits(moment.getMinutes())}:${twoDigits(moment.getSeconds())}`

  return `${dayOf(moment)}T${time}${sign}${hours}:${minutes}`
}

/** The exchanges of each day, by "2026-11-17", in the order they come. */
export function byDay<Item extends { exchange: Pick<Exchange, 'occurredAt'> }>(
  items: Item[],
): Map<string, Item[]> {
  const days = new Map<string, Item[]>()
  for (const item of items) {
    const day = dayOf(new Date(item.exchange.occurredAt))
    days.set(day, [...(days.get(day) ?? []), item])
  }
  return days
}

/** Planned, its moment passed: nobody said yet whether it was done or missed. */
export function isToHandle(exchange: Pick<Exchange, 'status' | 'occurredAt'>, now: Date): boolean {
  return exchange.status === 'planned' && new Date(exchange.occurredAt) < now
}

function twoDigits(value: number): string {
  return String(value).padStart(2, '0')
}
