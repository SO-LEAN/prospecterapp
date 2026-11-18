import { describe, expect, it } from 'vitest'
import { cityOf, dayLabel } from './slots'

describe('the words of the slots', () => {
  it('names a time zone after its city, its words spaced', () => {
    expect(cityOf('America/New_York')).toBe('New York')
  })

  it('names a day of the calendar the same on any clock', () => {
    expect(dayLabel('2026-11-30')).toBe('lun. 30 nov.')
  })
})
