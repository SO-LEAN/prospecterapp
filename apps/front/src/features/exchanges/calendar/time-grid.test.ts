import { describe, expect, it } from 'vitest'
import { hoursFromMidnight, placeInTheDay } from './time-grid'

const at = (hour: number, minute: number, durationInMinutes: number | null = 30) => ({
  exchange: { occurredAt: new Date(2026, 10, 17, hour, minute).toISOString(), durationInMinutes },
})
const placed = (items: ReturnType<typeof at>[]) =>
  placeInTheDay(items).map(({ start, length, lane, lanes }) => [start, length, lane, lanes])

describe('placeInTheDay', () => {
  it('places an exchange at its hour for its duration', () => {
    expect(placed([at(14, 30, 90)])).toEqual([[14.5, 1.5, 0, 1]])
  })

  it('gives a noted exchange the room of half an hour', () => {
    expect(placed([at(9, 0, null)])).toEqual([[9, 0.5, 0, 1]])
  })

  it('puts the exchanges overlapping side by side, the others full width', () => {
    expect(placed([at(14, 0, 60), at(14, 30), at(15, 0), at(17, 0)])).toEqual([
      [14, 1, 0, 2],
      [14.5, 0.5, 1, 2],
      [15, 0.5, 0, 1],
      [17, 0.5, 0, 1],
    ])
  })

  it('takes again a lane freed in the group', () => {
    expect(placed([at(14, 0, 120), at(14, 0), at(14, 30)])).toEqual([
      [14, 2, 0, 2],
      [14, 0.5, 1, 2],
      [14.5, 0.5, 1, 2],
    ])
  })

  it('places them by their start whatever their order, the longest first when they start together', () => {
    expect(placed([at(15, 0), at(9, 0, 30), at(9, 0, 120)])).toEqual([
      [9, 2, 0, 2],
      [9, 0.5, 1, 2],
      [15, 0.5, 0, 1],
    ])
  })

  it('never runs past midnight', () => {
    expect(placed([at(23, 45, 60)])).toEqual([[23.75, 0.25, 0, 1]])
  })
})

describe('hoursFromMidnight', () => {
  it('says where the line of now stands', () => {
    expect(hoursFromMidnight(new Date(2026, 10, 17, 10, 15))).toBe(10.25)
  })
})
