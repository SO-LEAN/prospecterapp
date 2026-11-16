import { describe, expect, it } from 'vitest'
import type { CalendarToDo } from './queries'
import { hoursOfDue, movedTo, SHOWN, shownOf, toDosByDay } from './to-dos'

const aToDo = (id: string, dueOn: string, dueAt: string | null = null): CalendarToDo => ({
  id,
  text: `Action ${id}`,
  dueOn,
  dueAt,
  doneAt: null,
  heldBy: { name: { firstName: 'Ada', lastName: 'Lovelace' } },
  contact: null,
  _links: { self: { href: `/api/to-dos/${id}`, method: 'GET' } },
})

describe('the to-dos of the calendar', () => {
  it('stand on their day as written, whatever the time zone of the browser', () => {
    const days = toDosByDay([
      aToDo('1', '2026-11-14'),
      aToDo('2', '2026-11-14', '10:00'),
      aToDo('3', '2026-11-15'),
    ])

    expect([...days.entries()].map(([day, toDos]) => [day, toDos.map(({ id }) => id)])).toEqual([
      ['2026-11-14', ['1', '2']],
      ['2026-11-15', ['3']],
    ])
  })

  it('stand at their hour in the grid, in hours from midnight', () => {
    expect([hoursOfDue('00:00'), hoursOfDue('08:30'), hoursOfDue('23:45')]).toEqual([0, 8.5, 23.75])
  })

  it('move to another day, keeping their text and their hour', () => {
    expect(movedTo(aToDo('1', '2026-11-14', '10:00'), '2026-11-16')).toEqual({
      text: 'Action 1',
      dueOn: '2026-11-16',
      dueAt: '10:00',
    })
  })

  it('show the exchanges, the to-dos or both, as the member filters them', () => {
    const calendar = { items: ['an exchange'], toDos: ['a to-do'] }

    expect(SHOWN.map((shown) => shownOf(calendar, shown))).toEqual([
      { items: ['an exchange'], toDos: ['a to-do'] },
      { items: ['an exchange'], toDos: [] },
      { items: [], toDos: ['a to-do'] },
    ])
  })
})
