import type { components } from '@/shared/api/schema'
import type { CalendarToDo } from './queries'

const MINUTES_IN_AN_HOUR = 60

/** What a to-do's revision sends: its text, its day, its hour. */
export type ToDoRevision = components['schemas']['ToDoInput']

/** What the calendar shows: the exchanges and the to-dos, or only one of them. */
export const SHOWN = ['both', 'exchanges', 'toDos'] as const
export type Shown = (typeof SHOWN)[number]

/** A period's exchanges and to-dos, as the member filters them. */
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
 * Each day's to-dos, keyed by "2026-11-17", in the order they come. Each one is on its written day, never converted
 * to an instant, whatever the browser's time zone (ADR 0134).
 */
export function toDosByDay<ToDo extends Pick<CalendarToDo, 'dueOn'>>(toDos: ToDo[]): Map<string, ToDo[]> {
  const days = new Map<string, ToDo[]>()
  for (const toDo of toDos) days.set(toDo.dueOn, [...(days.get(toDo.dueOn) ?? []), toDo])
  return days
}

/** "08:30" -> 8.5: where a to-do with an hour is in the grid. */
export function hoursOfDue(dueAt: string): number {
  const [hours = 0, minutes = 0] = dueAt.split(':').map(Number)
  return hours + minutes / MINUTES_IN_AN_HOUR
}

/** The revision of a to-do dropped on another day. Its text and its hour stay. */
export function movedTo(toDo: Pick<CalendarToDo, 'text' | 'dueAt'>, day: string): ToDoRevision {
  return { text: toDo.text, dueOn: day, dueAt: toDo.dueAt ?? null }
}
