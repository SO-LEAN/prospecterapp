import { describe, expect, it } from 'vitest'
import { appointmentMoment, cityOf, dayLabel } from './slots'

describe('the words of the slots', () => {
  it('names a time zone after its city, its words spaced', () => {
    expect(cityOf('America/New_York')).toBe('New York')
  })

  it('names a day of the calendar the same on any clock', () => {
    expect(dayLabel('2026-11-30')).toBe('lun. 30 nov.')
  })

  it('names the moment of an appointment in full, on the clock given', () => {
    expect(appointmentMoment('2026-11-24T09:00:00+01:00', 'Europe/Paris')).toBe('mardi 24 novembre à 9h00')
    expect(appointmentMoment('2026-11-24T09:05:00+01:00', 'Europe/London')).toBe('mardi 24 novembre à 8h05')
  })
})
