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

/** A day of the week as the form edits it: whether one may be booked then, and from when to when. */
export type DayWindow = { on: boolean; from: string; to: string }
export type WeekWindows = Record<Weekday, DayWindow>

const A_DAY_OFF: DayWindow = { on: false, from: '09:00', to: '12:00' }

/** Every quarter hour of a day, HH:MM, the times a window starts and ends on. */
export const QUARTER_HOURS = Array.from({ length: 24 * 4 }, (_, quarter) => {
  const hours = String(Math.floor(quarter / 4)).padStart(2, '0')
  const minutes = String((quarter % 4) * 15).padStart(2, '0')
  return `${hours}:${minutes}`
})

/** The week as the form shows it, from the windows the API answers: a day without one is off. */
export function weekOf(windows: Window[]): WeekWindows {
  return Object.fromEntries(
    WEEKDAYS.map((day) => {
      const window = windows.find((one) => one.weekday === day)
      return [day, window ? { on: true, from: window.from, to: window.to } : A_DAY_OFF]
    }),
  ) as WeekWindows
}

/** The windows the API takes: one for each day on. */
export function windowsOf(week: WeekWindows): Window[] {
  return WEEKDAYS.filter((day) => week[day].on).map((day) => ({
    weekday: day,
    from: week[day].from,
    to: week[day].to,
  }))
}

/** The window of the first day on, copied to every working day, the weekend as it was. */
export function copiedToWorkingDays(week: WeekWindows): WeekWindows {
  const first = WEEKDAYS.map((day) => week[day]).find((window) => window.on)
  if (!first) {
    return week
  }
  return { ...week, ...Object.fromEntries(WORKING_DAYS.map((day) => [day, { ...first }])) }
}
