import { formatDay, formatHour } from '@/shared/lib/format'
import { addDays, isSameDay, startOfDay } from '../calendar/dates'
import type { Exchange } from '../queries'

const MINUTE_MS = 60_000
const MINUTES_IN_AN_HOUR = 60
const DAYS_IN_A_WEEK = 7

const relativeFormat = new Intl.RelativeTimeFormat('fr-FR', { style: 'short' })
const weekdayFormat = new Intl.DateTimeFormat('fr-FR', { weekday: 'long' })

/** "dans 25 min", "dans 2 h": what is left before it starts; nothing once it started. */
export function startsIn(occurredAt: string, now: Date): string | null {
  const minutes = Math.ceil((new Date(occurredAt).getTime() - now.getTime()) / MINUTE_MS)

  if (minutes <= 0) return null

  return minutes < MINUTES_IN_AN_HOUR
    ? relativeFormat.format(minutes, 'minute')
    : relativeFormat.format(Math.floor(minutes / MINUTES_IN_AN_HOUR), 'hour')
}

/** From its start until the end of its duration; an exchange without a duration is never in progress. */
export function isInProgress(
  { occurredAt, durationInMinutes }: Pick<Exchange, 'occurredAt' | 'durationInMinutes'>,
  now: Date,
): boolean {
  const start = new Date(occurredAt).getTime()
  const end = start + (durationInMinutes ?? 0) * MINUTE_MS

  return start <= now.getTime() && now.getTime() < end
}

/** "demain 9h00", "lundi 14h00" within the week, then "ven. 20 nov. 14h00" ("jeu. 7 janv. 2027 9h00" in another year), in the time of the browser. */
export function whenToCome(occurredAt: string, now: Date): string {
  const moment = new Date(occurredAt)
  const today = startOfDay(now)
  const hour = formatHour(occurredAt)

  if (isSameDay(moment, addDays(today, 1))) return `${relativeDayOf(1)} ${hour}`
  if (moment < addDays(today, DAYS_IN_A_WEEK)) return `${weekdayFormat.format(moment)} ${hour}`

  return `${formatDay(moment, now)} ${hour}`
}

const relativeDayFormat = new Intl.RelativeTimeFormat('fr-FR', { numeric: 'auto' })

function relativeDayOf(days: number): string {
  return relativeDayFormat.format(days, 'day')
}
