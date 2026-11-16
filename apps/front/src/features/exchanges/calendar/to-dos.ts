import type { ToDoInput } from '@/features/to-dos/queries'
import type { CalendarToDo } from './queries'

const MINUTES_IN_AN_HOUR = 60

/** What the calendar shows: the exchanges and the to-dos, or one of them. */
export const SHOWN = ['both', 'exchanges', 'toDos'] as const
export type Shown = (typeof SHOWN)[number]

/** The exchanges and the to-dos of a period, as the member filters them. */
export function shownOf<Exchange, ToDo>(
  calendar: { items: Exchange[]; toDos: ToDo[] },
  shown: Shown,
): { items: Exchange[]; toDos: ToDo[] } {
  return {
    items: shown === 'toDos' ? [] : calendar.items,
    toDos: shown === 'exchanges' ? [] : calendar.toDos,
  }
}

/**
 * The to-dos of each day, by "2026-11-17", in the order they come: on the day written, never converted to an instant,
 * whatever the time zone of the browser (ADR 0134).
 */
export function toDosByDay<ToDo extends Pick<CalendarToDo, 'dueOn'>>(toDos: ToDo[]): Map<string, ToDo[]> {
  const days = new Map<string, ToDo[]>()
  for (const toDo of toDos) days.set(toDo.dueOn, [...(days.get(toDo.dueOn) ?? []), toDo])
  return days
}

/** "08:30" -> 8.5: where a to-do with an hour stands in the grid. */
export function hoursOfDue(dueAt: string): number {
  const [hours = 0, minutes = 0] = dueAt.split(':').map(Number)
  return hours + minutes / MINUTES_IN_AN_HOUR
}

/** What revises a to-do dropped on another day: its text and its hour kept. */
export function movedTo(toDo: Pick<CalendarToDo, 'text' | 'dueAt'>, day: string): ToDoInput {
  return { text: toDo.text, dueOn: day, dueAt: toDo.dueAt ?? null }
}
