import type { Window } from './mutations'

export const WEEKDAYS = [
  'monday',
  'tuesday',
  'wednesday',
  'thursday',
  'friday',
  'saturday',
  'sunday',
] as const
export type Weekday = (typeof WEEKDAYS)[number]

const WORKING_DAYS: readonly Weekday[] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']

/** A weekday as the form edits it: whether bookers can book on that day, and from when to when. */
export type DayWindow = { on: boolean; from: string; to: string }
export type WeekWindows = Record<Weekday, DayWindow>

const A_DAY_OFF: DayWindow = { on: false, from: '09:00', to: '12:00' }

/** Every quarter hour of a day, written HH:MM. A window starts and ends on one of these times. */
export const QUARTER_HOURS = Array.from({ length: 24 * 4 }, (_, quarter) => {
  const hours = String(Math.floor(quarter / 4)).padStart(2, '0')
  const minutes = String((quarter % 4) * 15).padStart(2, '0')
  return `${hours}:${minutes}`
})

/** The week as the form shows it, built from the windows that the API answers. A day without a window is off. */
export function weekOf(windows: Window[]): WeekWindows {
  return Object.fromEntries(
    WEEKDAYS.map((day) => {
      const window = windows.find((one) => one.weekday === day)
      return [day, window ? { on: true, from: window.from, to: window.to } : A_DAY_OFF]
    }),
  ) as WeekWindows
}

/** The windows that the API takes: one for each day that is on. */
export function windowsOf(week: WeekWindows): Window[] {
  return WEEKDAYS.filter((day) => week[day].on).map((day) => ({
    weekday: day,
    from: week[day].from,
    to: week[day].to,
  }))
}

/** Copies the first active day's window to every working day. The weekend stays as it was. */
export function copiedToWorkingDays(week: WeekWindows): WeekWindows {
  const first = WEEKDAYS.map((day) => week[day]).find((window) => window.on)
  if (!first) {
    return week
  }
  return { ...week, ...Object.fromEntries(WORKING_DAYS.map((day) => [day, { ...first }])) }
}
