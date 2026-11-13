import { describe, expect, it } from 'vitest'
import { nextQuarterHour, SHORTCUTS, slotOf } from './planning'

/** Friday 13 November 2026, 10:05, in the time of the browser. */
const FRIDAY = new Date('2026-11-13T10:05')

describe('the planning', () => {
  it('starts at the next quarter of an hour', () => {
    expect(nextQuarterHour(FRIDAY)).toEqual(new Date('2026-11-13T10:15'))
    expect(nextQuarterHour(new Date('2026-11-13T10:15'))).toEqual(new Date('2026-11-13T10:30'))
  })

  it('offers tomorrow at 9, next Monday at 9 and a week from now', () => {
    expect(SHORTCUTS.tomorrowAt9(FRIDAY)).toEqual(new Date('2026-11-14T09:00'))
    expect(SHORTCUTS.mondayAt9(FRIDAY)).toEqual(new Date('2026-11-16T09:00'))
    expect(SHORTCUTS.inAWeek(FRIDAY)).toEqual(new Date('2026-11-20T10:15'))
  })

  it('takes next Monday on a Monday, and tomorrow on a Sunday', () => {
    expect(SHORTCUTS.mondayAt9(new Date('2026-11-16T08:00'))).toEqual(new Date('2026-11-23T09:00'))
    expect(SHORTCUTS.mondayAt9(new Date('2026-11-15T20:00'))).toEqual(new Date('2026-11-16T09:00'))
  })

  it('names a slot only once its moment and its duration are', () => {
    expect(slotOf({ date: '2026-11-17', time: '14:00', durationInMinutes: 30 })).toEqual({
      startsAt: new Date('2026-11-17T14:00').toISOString(),
      durationInMinutes: 30,
    })
    expect(slotOf({ date: '2026-11-17', time: '', durationInMinutes: 30 })).toBeNull()
    expect(slotOf({ date: '2026-11-17', time: '14:00', durationInMinutes: 481 })).toBeNull()
    expect(slotOf({ date: '2026-11-17', time: '14:00', durationInMinutes: Number.NaN })).toBeNull()
  })
})
