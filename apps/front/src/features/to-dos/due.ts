import type { ToDo } from './queries'

const MONDAY = 1
const WEEK = 7

/** "2026-11-13": a moment's day in the browser's time zone, in the format the API reads for a due day. */
export const dayOf = (moment: Date) =>
  [moment.getFullYear(), moment.getMonth() + 1, moment.getDate()]
    .map((part) => String(part).padStart(2, '0'))
    .join('-')

/** "08:30": a moment's hour in the browser's time zone. */
const hourOf = (moment: Date) =>
  [moment.getHours(), moment.getMinutes()].map((part) => String(part).padStart(2, '0')).join(':')

const daysAfter = (day: Date, days: number) =>
  new Date(day.getFullYear(), day.getMonth(), day.getDate() + days)

/** The days a shortcut sets: today, tomorrow, next Monday, a week from today. */
export const DUE_SHORTCUTS = {
  today: (now: Date) => dayOf(now),
  tomorrow: (now: Date) => dayOf(daysAfter(now, 1)),
  monday: (now: Date) => dayOf(daysAfter(now, ((MONDAY - now.getDay() + WEEK - 1) % WEEK) + 1)),
  inAWeek: (now: Date) => dayOf(daysAfter(now, WEEK)),
} as const

export type DueShortcut = keyof typeof DUE_SHORTCUTS

/** Still to do after its day, or after its hour on its day. */
export const isOverdue = (toDo: Pick<ToDo, 'dueOn' | 'dueAt' | 'doneAt'>, now: Date) => {
  if (toDo.doneAt) {
    return false
  }
  const today = dayOf(now)
  return (
    toDo.dueOn < today || (toDo.dueOn === today && Boolean(toDo.dueAt) && (toDo.dueAt ?? '') < hourOf(now))
  )
}

const dayFormat = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' })

/** "2026-11-14" and "08:30" -> "14 nov. 2026, 8h30": a calendar day, as the prospector wrote it. */
export const formatDue = ({ dueOn, dueAt }: Pick<ToDo, 'dueOn' | 'dueAt'>) => {
  const [year = 0, month = 1, day = 1] = dueOn.split('-').map(Number)
  const shownDay = dayFormat.format(new Date(year, month - 1, day))
  if (!dueAt) {
    return shownDay
  }
  const [hours = 0, minutes = 0] = dueAt.split(':').map(Number)
  return `${shownDay}, ${hours}h${String(minutes).padStart(2, '0')}`
}

/** The API orders the to-dos, those to do first. The list splits them and folds the done ones. */
export const split = (toDos: ToDo[]) => ({
  toDo: toDos.filter((toDo) => !toDo.doneAt),
  done: toDos.filter((toDo) => Boolean(toDo.doneAt)),
})
