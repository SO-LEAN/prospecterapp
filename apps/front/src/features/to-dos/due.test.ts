import { describe, expect, it } from 'vitest'
import { DUE_SHORTCUTS, dayOf, formatDue, isOverdue, split } from './due'
import type { ToDo } from './queries'

/** Friday 13 November 2026, 10:00 in the time of the browser. */
const FRIDAY = new Date(2026, 10, 13, 10, 0)

const aToDo = (toDo: Partial<ToDo>): ToDo => ({
  id: '0190a1c0-0000-7000-8000-000000000001',
  text: 'Envoyer mon CV',
  dueOn: '2026-11-14',
  dueAt: null,
  doneAt: null,
  heldBy: { name: { firstName: 'Michel', lastName: 'Maier' }, email: null },
  contact: null,
  _links: { self: { href: '/api/to-dos/0190a1c0-0000-7000-8000-000000000001', method: 'GET' } },
  ...toDo,
})

describe('the due shortcuts', () => {
  it('set today, tomorrow, next Monday and a week from today', () => {
    expect(Object.values(DUE_SHORTCUTS).map((shortcut) => shortcut(FRIDAY))).toEqual([
      '2026-11-13',
      '2026-11-14',
      '2026-11-16',
      '2026-11-20',
    ])
  })

  it('set the Monday after, on a Monday', () => {
    expect(DUE_SHORTCUTS.monday(new Date(2026, 10, 16, 9))).toBe('2026-11-23')
  })

  it('cross the end of a month', () => {
    expect(DUE_SHORTCUTS.tomorrow(new Date(2026, 10, 30, 9))).toBe('2026-12-01')
    expect(dayOf(new Date(2026, 0, 5))).toBe('2026-01-05')
  })
})

describe('a to-do overdue', () => {
  it('is still to do past its day', () => {
    expect(isOverdue(aToDo({ dueOn: '2026-11-12' }), FRIDAY)).toBe(true)
  })

  it('is still to do past its hour on its day', () => {
    expect(isOverdue(aToDo({ dueOn: '2026-11-13', dueAt: '09:30' }), FRIDAY)).toBe(true)
    expect(isOverdue(aToDo({ dueOn: '2026-11-13', dueAt: '10:30' }), FRIDAY)).toBe(false)
  })

  it('is not, due today without an hour, or later', () => {
    expect(isOverdue(aToDo({ dueOn: '2026-11-13' }), FRIDAY)).toBe(false)
    expect(isOverdue(aToDo({ dueOn: '2026-11-14' }), FRIDAY)).toBe(false)
  })

  it('is not, once done', () => {
    expect(isOverdue(aToDo({ dueOn: '2026-11-12', doneAt: '2026-11-13T09:00:00+00:00' }), FRIDAY)).toBe(false)
  })
})

describe('the to-dos of a list', () => {
  it('are split, those to do apart from those done, in the order of the API', () => {
    const first = aToDo({ id: 'a' })
    const done = aToDo({ id: 'b', doneAt: '2026-11-13T09:00:00+00:00' })
    const second = aToDo({ id: 'c' })

    expect(split([first, done, second])).toEqual({ toDo: [first, second], done: [done] })
  })
})

describe('a due day', () => {
  it('reads as written, with its hour when said', () => {
    expect(formatDue({ dueOn: '2026-11-14', dueAt: null })).toBe('14 nov. 2026')
    expect(formatDue({ dueOn: '2026-11-14', dueAt: '08:30' })).toBe('14 nov. 2026, 8h30')
  })
})
