import { afterEach, describe, expect, it, vi } from 'vitest'
import { parseDay } from './dates'
import { landing, minutesAt, slotBetween, spotUnder, stretched, withTheGesture } from './gestures'
import type { CalendarExchange } from './queries'

const TUESDAY = parseDay('2026-11-17')
const at = (hour: number, minute = 0) => new Date(2026, 10, 17, hour, minute)

/** A column of 24 hours of 2 400 pixels: 100 pixels an hour, from the top of the page. */
const aColumn = (day: string, hours = true) => {
  const column = document.createElement('ol')
  column.setAttribute('data-day', day)
  if (hours) column.setAttribute('data-hours', '')
  column.getBoundingClientRect = () => ({ top: 0, height: 2400 }) as DOMRect
  return column
}

describe('spotUnder', () => {
  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('reads the day and the minute of the column of hours under the pointer', () => {
    const column = aColumn('2026-11-18')
    const chip = column.appendChild(document.createElement('li'))
    document.elementFromPoint = () => chip

    expect(spotUnder(10, 925)).toEqual({ day: parseDay('2026-11-18'), minutes: 9 * 60 + 15 })
  })

  it('reads the day alone of a day of the month', () => {
    const cell = aColumn('2026-11-20', false)
    document.elementFromPoint = () => cell

    expect(spotUnder(10, 925)).toEqual({ day: parseDay('2026-11-20'), minutes: null })
  })

  it('finds no spot out of the grid', () => {
    document.elementFromPoint = () => document.body

    expect(spotUnder(0, 0)).toBeNull()
  })
})

describe('minutesAt', () => {
  it('snaps down to the quarter, inside the day', () => {
    expect(minutesAt(aColumn('2026-11-17'), 1449)).toBe(14 * 60 + 15)
    expect(minutesAt(aColumn('2026-11-17'), -50)).toBe(0)
    expect(minutesAt(aColumn('2026-11-17'), 2450)).toBe(23 * 60 + 45)
  })
})

describe('landing', () => {
  it('lands at the minute pointed at, less where the exchange was grabbed', () => {
    expect(landing(at(14), { day: parseDay('2026-11-18'), minutes: 9 * 60 + 15 }, 15)).toEqual(
      new Date(2026, 10, 18, 9, 0),
    )
  })

  it('keeps its hour on a day of the month', () => {
    expect(landing(at(14, 30), { day: parseDay('2026-11-20'), minutes: null }, 0)).toEqual(
      new Date(2026, 10, 20, 14, 30),
    )
  })

  it('stays in the day', () => {
    expect(landing(at(14), { day: TUESDAY, minutes: 0 }, 60)).toEqual(at(0))
  })
})

describe('stretched', () => {
  it('lasts until the quarter pointed at, included', () => {
    expect(stretched(at(14), 14 * 60 + 50)).toBe(60)
  })

  it('lasts a quarter at least, 8 hours at most, never past midnight', () => {
    expect(stretched(at(14), 13 * 60)).toBe(15)
    expect(stretched(at(9), 23 * 60)).toBe(480)
    expect(stretched(at(23), 23 * 60 + 50)).toBe(60)
  })
})

describe('slotBetween', () => {
  it('starts at the first quarter chosen and ends after the last, whichever way it was dragged', () => {
    expect(slotBetween(TUESDAY, 11 * 60, 10 * 60)).toEqual({ startsAt: at(10), durationInMinutes: 75 })
  })

  it('lasts half an hour for a click', () => {
    expect(slotBetween(TUESDAY, 10 * 60, 10 * 60)).toEqual({ startsAt: at(10), durationInMinutes: 30 })
  })

  it('lasts 8 hours at most', () => {
    expect(slotBetween(TUESDAY, 6 * 60, 20 * 60).durationInMinutes).toBe(480)
  })
})

describe('withTheGesture', () => {
  const anExchange = (id: string) =>
    ({ exchange: { id, occurredAt: at(14).toISOString(), durationInMinutes: 30 } }) as CalendarExchange

  it('shows the exchange being moved at its new moment, for its new duration, the others as they are', () => {
    const [moved, other] = withTheGesture([anExchange('a'), anExchange('b')], {
      id: 'a',
      occurredAt: at(16),
      durationInMinutes: 60,
    })

    expect(moved?.exchange).toMatchObject({ occurredAt: at(16).toISOString(), durationInMinutes: 60 })
    expect(other?.exchange).toMatchObject({ occurredAt: at(14).toISOString(), durationInMinutes: 30 })
  })
})
