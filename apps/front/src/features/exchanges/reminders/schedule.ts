import type { DayExchange } from '../my-day/queries'
import type { Exchange } from '../queries'

const MINUTE_MS = 60_000

/** « Dans 5 min »: the reminder comes again that long after. */
export const SNOOZE_MS = 5 * MINUTE_MS

/** A reminder comes late by this much at most: the application opened after its moment, or the tab asleep. */
const LATE_AT_MOST_MS = MINUTE_MS

export type ReminderSettings = { enabled: boolean; minutesBefore: number }

/** The reminders a tab already showed, by exchange and moment: shown once, or to show again at `again`. */
export type Reminded = Record<string, { occurredAt: string; again: string | null }>

/** An exchange moved is another reminder: its new moment is reminded of, whatever was of the former one. */
export const reminderOf = (exchange: Pick<Exchange, 'id' | 'occurredAt'>) =>
  `${exchange.id}@${exchange.occurredAt}`

/**
 * Those to show now: their moment came, `minutesBefore` its exchange or the one asked by « Dans 5 min », and passed by
 * less than a minute after the start of the exchange, or after that moment when later.
 */
export function remindersDue(
  items: DayExchange[],
  settings: ReminderSettings,
  reminded: Reminded,
  now: Date,
): DayExchange[] {
  return items.filter((item) => {
    const moment = momentOf(item, settings, reminded)
    if (moment === null) {
      return false
    }
    const start = new Date(item.exchange.occurredAt).getTime()
    return moment <= now.getTime() && now.getTime() < Math.max(start, moment) + LATE_AT_MOST_MS
  })
}

/** The moment of the next reminder to come, none when there is none. */
export function nextReminderAt(
  items: DayExchange[],
  settings: ReminderSettings,
  reminded: Reminded,
  now: Date,
): Date | null {
  const toCome = items
    .map((item) => momentOf(item, settings, reminded))
    .filter((moment): moment is number => moment !== null && moment > now.getTime())

  return toCome.length > 0 ? new Date(Math.min(...toCome)) : null
}

function momentOf(item: DayExchange, settings: ReminderSettings, reminded: Reminded): number | null {
  const { exchange } = item
  if (!settings.enabled || exchange.status !== 'planned') {
    return null
  }
  const shown = reminded[reminderOf(exchange)]
  if (shown) {
    return shown.again === null ? null : new Date(shown.again).getTime()
  }
  return new Date(exchange.occurredAt).getTime() - settings.minutesBefore * MINUTE_MS
}
