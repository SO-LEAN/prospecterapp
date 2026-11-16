import { describe, expect, it } from 'vitest'
import type { Reminded } from './schedule'
import { nextToDoReminderAt, type RemindedToDo, reminderOfToDo, toDoRemindersDue } from './to-do-schedule'

const at = (hours: number, minutes: number) => new Date(2026, 10, 13, hours, minutes)
const TEN_MINUTES_BEFORE = { enabled: true, minutesBefore: 10 }

const aToDo = (dueAt: string | null, dueOn = '2026-11-13', doneAt: string | null = null): RemindedToDo => ({
  id: '0190a1c0-0000-7000-8000-000000000001',
  text: 'Envoyer mon CV',
  dueOn,
  dueAt,
  doneAt,
})

const due = (toDo: RemindedToDo, now: Date, reminded: Reminded = {}) =>
  toDoRemindersDue([toDo], TEN_MINUTES_BEFORE, reminded, now).length === 1

describe('the reminder of a to-do', () => {
  it('comes at the delay of the exchanges before its hour, until a minute after it', () => {
    const atTen = aToDo('10:00')

    expect([
      due(atTen, at(9, 49)),
      due(atTen, at(9, 50)),
      due(atTen, at(10, 0)),
      due(atTen, at(10, 1)),
    ]).toEqual([false, true, true, false])
  })

  it('comes at 9:00 on its day without an hour, or once the application opens later that day', () => {
    const ofTheDay = aToDo(null)

    expect([due(ofTheDay, at(8, 59)), due(ofTheDay, at(9, 0)), due(ofTheDay, at(17, 30))]).toEqual([
      false,
      true,
      true,
    ])
  })

  it('never comes for a to-do of another day, done, or already reminded', () => {
    expect([
      due(aToDo(null, '2026-11-12'), at(10, 0)),
      due(aToDo(null, '2026-11-13', '2026-11-13T08:00:00+01:00'), at(10, 0)),
      due(aToDo(null), at(10, 0), {
        [reminderOfToDo(aToDo(null))]: { occurredAt: at(9, 0).toISOString(), again: null },
      }),
    ]).toEqual([false, false, false])
  })

  it('comes again when asked for later', () => {
    const reminded = {
      [reminderOfToDo(aToDo(null))]: { occurredAt: at(9, 0).toISOString(), again: at(9, 5).toISOString() },
    }

    expect([due(aToDo(null), at(9, 4), reminded), due(aToDo(null), at(9, 5), reminded)]).toEqual([
      false,
      true,
    ])
  })

  it('is none when the reminders are off', () => {
    expect(toDoRemindersDue([aToDo(null)], { enabled: false, minutesBefore: 10 }, {}, at(10, 0))).toEqual([])
  })

  it('tells when the next one comes', () => {
    expect(nextToDoReminderAt([aToDo('10:00'), aToDo(null)], TEN_MINUTES_BEFORE, {}, at(8, 0))).toEqual(
      at(9, 0),
    )
    expect(nextToDoReminderAt([aToDo('10:00')], TEN_MINUTES_BEFORE, {}, at(9, 55))).toBeNull()
  })
})
