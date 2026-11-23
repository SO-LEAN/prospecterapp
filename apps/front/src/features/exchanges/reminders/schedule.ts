import type { DayExchange } from '../my-day/queries'
import type { Exchange } from '../queries'

const MINUTE_MS = 60_000

/** « Dans 5 min »: the reminder comes again this long after. */
export const SNOOZE_MS = 5 * MINUTE_MS

/** A reminder comes this late at most, when the application opens after its moment or the tab was asleep. */
const LATE_AT_MOST_MS = MINUTE_MS

export type ReminderSettings = { enabled: boolean; minutesBefore: number }

/** The reminders that a tab already showed, by exchange and moment. Each one was shown once, or shows again at `again`. */
export type Reminded = Record<string, { occurredAt: string; again: string | null }>

/** A moved exchange makes a new reminder. Its new moment gets a reminder, whatever happened to the former one. */
export const reminderOf = (exchange: Pick<Exchange, 'id' | 'occurredAt'>) =>
  `${exchange.id}@${exchange.occurredAt}`

/**
 * The reminders to show now. Their moment has come: `minutesBefore` the exchange, or the moment that « Dans 5 min »
 * asked for. It is less than a minute past the exchange's start, or past that moment when it is later.
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

/** The next upcoming reminder's moment, or none when there is none. */
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
