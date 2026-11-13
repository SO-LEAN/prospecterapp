import { afterEach, describe, expect, it, vi } from 'vitest'
import {
  addDays,
  byDay,
  dayOf,
  daysOf,
  isToHandle,
  momentWithItsOffset,
  neighbourOf,
  parseDay,
  periodOf,
  queryOf,
} from './dates'

const day = (written: string) => parseDay(written)
const daysWritten = (period: ReturnType<typeof periodOf>) => daysOf(period).map(dayOf)

describe('periodOf', () => {
  it('shows a month from the Monday of its first week to the Sunday of its last', () => {
    const period = periodOf('month', day('2026-11-13'))

    expect(dayOf(period.first)).toBe('2026-10-26')
    expect(dayOf(period.end)).toBe('2026-12-07')
    expect(daysOf(period)).toHaveLength(42)
  })

  it('shows a month starting on a Monday from its first day', () => {
    expect(daysWritten(periodOf('month', day('2027-02-10')))).toEqual(
      expect.arrayContaining(['2027-02-01', '2027-02-28']),
    )
    expect(dayOf(periodOf('month', day('2027-02-10')).first)).toBe('2027-02-01')
    expect(dayOf(periodOf('month', day('2027-02-10')).end)).toBe('2027-03-01')
  })

  it('shows a week from its Monday to its Sunday', () => {
    expect(daysWritten(periodOf('week', day('2026-11-15')))).toEqual([
      '2026-11-09',
      '2026-11-10',
      '2026-11-11',
      '2026-11-12',
      '2026-11-13',
      '2026-11-14',
      '2026-11-15',
    ])
  })

  it('shows a day alone', () => {
    expect(daysWritten(periodOf('day', day('2026-11-17')))).toEqual(['2026-11-17'])
  })

  it('lists the days of the month in the agenda', () => {
    const period = periodOf('agenda', day('2026-11-17'))

    expect([dayOf(period.first), dayOf(period.end)]).toEqual(['2026-11-01', '2026-12-01'])
  })
})

/** The time of Paris, which goes from summer to winter at 1:00 UTC on 25 October 2026, whatever the machine runs in. */
const END_OF_SUMMER_TIME = Date.UTC(2026, 9, 25, 1)
const PARIS_IN_SUMMER = -120
const PARIS_IN_WINTER = -60

describe('in a time changing in the period', () => {
  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('counts the days of a period by the calendar, not by 24 hours', () => {
    expect(daysWritten(periodOf('month', day('2026-10-13')))).toHaveLength(35)
    expect(daysWritten(periodOf('week', day('2026-10-25'))).at(-1)).toBe('2026-10-25')
  })

  it('sends each bound at its own offset', () => {
    vi.spyOn(Date.prototype, 'getTimezoneOffset').mockImplementation(function (this: Date) {
      return this.getTime() < END_OF_SUMMER_TIME ? PARIS_IN_SUMMER : PARIS_IN_WINTER
    })

    expect(queryOf(periodOf('week', day('2026-10-25')))).toEqual({
      from: '2026-10-19T00:00:00+02:00',
      to: '2026-10-26T00:00:00+01:00',
    })
  })
})

describe('neighbourOf', () => {
  it('goes from a month to the next or the previous one', () => {
    expect(dayOf(neighbourOf('month', day('2026-01-31'), 1))).toBe('2026-02-01')
    expect(dayOf(neighbourOf('agenda', day('2026-01-15'), -1))).toBe('2025-12-01')
  })

  it('goes from a week to the next, from a day to the next', () => {
    expect(dayOf(neighbourOf('week', day('2026-11-13'), 1))).toBe('2026-11-20')
    expect(dayOf(neighbourOf('day', day('2026-11-13'), -1))).toBe('2026-11-12')
  })
})

describe('queryOf', () => {
  it('sends the bounds of the period at the offset of the browser, the end excluded', () => {
    const query = queryOf(periodOf('day', day('2026-11-17')))

    expect(new Date(query.from).getTime()).toBe(new Date(2026, 10, 17).getTime())
    expect(new Date(query.to).getTime()).toBe(new Date(2026, 10, 18).getTime())
  })

  it('writes a moment with the offset of its time', () => {
    expect(momentWithItsOffset(new Date(2026, 10, 17, 9, 5))).toMatch(/^2026-11-17T09:05:00[+-]\d{2}:\d{2}$/)
  })
})

describe('addDays', () => {
  it('keeps midnight across the change of time', () => {
    expect(addDays(day('2026-10-24'), 2).getHours()).toBe(0)
  })
})

describe('isToHandle', () => {
  const now = new Date(2026, 10, 13, 10, 0)

  it('says a planned exchange whose moment passed is to handle', () => {
    expect(
      isToHandle({ status: 'planned', occurredAt: new Date(2026, 10, 13, 9, 0).toISOString() }, now),
    ).toBe(true)
  })

  it('leaves alone what is to come, and what is done, missed or cancelled', () => {
    expect(
      isToHandle({ status: 'planned', occurredAt: new Date(2026, 10, 13, 11, 0).toISOString() }, now),
    ).toBe(false)
    expect(isToHandle({ status: 'done', occurredAt: new Date(2026, 10, 13, 9, 0).toISOString() }, now)).toBe(
      false,
    )
  })
})

describe('byDay', () => {
  it('groups the exchanges by the day they happen in the time of the browser', () => {
    const at = (moment: Date) => ({ exchange: { occurredAt: moment.toISOString() } })
    const items = [
      at(new Date(2026, 10, 17, 9)),
      at(new Date(2026, 10, 17, 14)),
      at(new Date(2026, 10, 18, 0)),
    ]

    expect([...byDay(items).entries()].map(([day, ofTheDay]) => [day, ofTheDay.length])).toEqual([
      ['2026-11-17', 2],
      ['2026-11-18', 1],
    ])
  })
})
