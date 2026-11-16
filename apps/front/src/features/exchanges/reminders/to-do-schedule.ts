import type { components } from '@/shared/api/schema'
import { dayOf } from '../calendar/dates'
import { type Reminded, type ReminderSettings, reminderOf } from './schedule'

const MINUTE_MS = 60_000

/** A to-do without an hour is reminded at that hour of its day (ADR 0134). */
const REMINDED_AT_WITHOUT_AN_HOUR = { hours: 9, minutes: 0 }

/** A reminder comes late by this much at most after the hour of its to-do. */
const LATE_AT_MOST_MS = MINUTE_MS

export type RemindedToDo = Pick<
  components['schemas']['ToDoResource'],
  'id' | 'text' | 'dueOn' | 'dueAt' | 'doneAt'
>

/**
 * The moment its due stands for in the time of the browser: its hour on its day, or the start of its day; a due
 * changed is another reminder.
 */
export function dueMomentOf({ dueOn, dueAt }: Pick<RemindedToDo, 'dueOn' | 'dueAt'>): Date {
  const [year = 0, month = 1, day = 1] = dueOn.split('-').map(Number)
  const [hours = 0, minutes = 0] = (dueAt ?? '00:00').split(':').map(Number)
  return new Date(year, month - 1, day, hours, minutes)
}

/** A to-do as the reminders shown remember it, among the exchanges: by its id and the moment of its due. */
export const asReminder = (toDo: Pick<RemindedToDo, 'id' | 'dueOn' | 'dueAt'>) => ({
  id: toDo.id,
  occurredAt: dueMomentOf(toDo).toISOString(),
})

export const reminderOfToDo = (toDo: Pick<RemindedToDo, 'id' | 'dueOn' | 'dueAt'>) =>
  reminderOf(asReminder(toDo))

/**
 * Those to show now: a to-do with an hour `minutesBefore` it, as an exchange, until a minute after its hour; one
 * without an hour from 9:00 on its day, or later that day once the application opens; or when asked again later.
 */
export function toDoRemindersDue(
  toDos: RemindedToDo[],
  settings: ReminderSettings,
  reminded: Reminded,
  now: Date,
): RemindedToDo[] {
  return toDos.filter((toDo) => {
    const moment = momentOf(toDo, settings, reminded)
    return moment !== null && moment <= now.getTime() && now.getTime() < lastMomentOf(toDo, moment)
  })
}

/** The moment of the next reminder of a to-do to come, none when there is none. */
export function nextToDoReminderAt(
  toDos: RemindedToDo[],
  settings: ReminderSettings,
  reminded: Reminded,
  now: Date,
): Date | null {
  const toCome = toDos
    .map((toDo) => momentOf(toDo, settings, reminded))
    .filter((moment): moment is number => moment !== null && moment > now.getTime())

  return toCome.length > 0 ? new Date(Math.min(...toCome)) : null
}

function momentOf(toDo: RemindedToDo, settings: ReminderSettings, reminded: Reminded): number | null {
  if (!settings.enabled || toDo.doneAt) {
    return null
  }
  const shown = reminded[reminderOfToDo(toDo)]
  if (shown) {
    return shown.again === null ? null : new Date(shown.again).getTime()
  }
  const due = dueMomentOf(toDo)
  if (toDo.dueAt) {
    return due.getTime() - settings.minutesBefore * MINUTE_MS
  }
  return new Date(due).setHours(REMINDED_AT_WITHOUT_AN_HOUR.hours, REMINDED_AT_WITHOUT_AN_HOUR.minutes)
}

/** A minute after its hour, or after the moment asked again when later; the end of its day without an hour. */
function lastMomentOf(toDo: RemindedToDo, moment: number): number {
  if (!toDo.dueAt) {
    const theDayAfter = dueMomentOf(toDo)
    theDayAfter.setDate(theDayAfter.getDate() + 1)
    return dayOf(new Date(moment)) === toDo.dueOn ? theDayAfter.getTime() : moment + LATE_AT_MOST_MS
  }
  return Math.max(dueMomentOf(toDo).getTime(), moment) + LATE_AT_MOST_MS
}
