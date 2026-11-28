import { describe, expect, it } from 'vitest'
import { publicHolidayOn } from './public-holidays'

const day = (written: string) => new Date(`${written}T00:00`)

/** Each year's Easter Monday, Ascension Day and Whit Monday, from an independent Easter computation. */
const MOVABLE_FEASTS = [
  ['2020-04-13', '2020-05-21', '2020-06-01'],
  ['2021-04-05', '2021-05-13', '2021-05-24'],
  ['2022-04-18', '2022-05-26', '2022-06-06'],
  ['2023-04-10', '2023-05-18', '2023-05-29'],
  ['2024-04-01', '2024-05-09', '2024-05-20'],
  ['2025-04-21', '2025-05-29', '2025-06-09'],
  ['2026-04-06', '2026-05-14', '2026-05-25'],
  ['2027-03-29', '2027-05-06', '2027-05-17'],
  ['2028-04-17', '2028-05-25', '2028-06-05'],
  ['2029-04-02', '2029-05-10', '2029-05-21'],
  ['2030-04-22', '2030-05-30', '2030-06-10'],
  ['2031-04-14', '2031-05-22', '2031-06-02'],
  ['2032-03-29', '2032-05-06', '2032-05-17'],
  ['2033-04-18', '2033-05-26', '2033-06-06'],
  ['2034-04-10', '2034-05-18', '2034-05-29'],
  ['2035-03-26', '2035-05-03', '2035-05-14'],
  ['2036-04-14', '2036-05-22', '2036-06-02'],
  ['2037-04-06', '2037-05-14', '2037-05-25'],
  ['2038-04-26', '2038-06-03', '2038-06-14'],
  ['2039-04-11', '2039-05-19', '2039-05-30'],
  ['2040-04-02', '2040-05-10', '2040-05-21'],
] as const

const EASTER_MONDAYS = MOVABLE_FEASTS.map(([easterMonday]) => easterMonday)
const ASCENSION_DAYS = MOVABLE_FEASTS.map(([, ascension]) => ascension)
const WHIT_MONDAYS = MOVABLE_FEASTS.map(([, , whitMonday]) => whitMonday)

describe('publicHolidayOn', () => {
  it.each([
    ['2026-01-01', 'newYearsDay'],
    ['2026-05-01', 'labourDay'],
    ['2026-05-08', 'victoryInEurope'],
    ['2026-07-14', 'bastilleDay'],
    ['2026-08-15', 'assumption'],
    ['2026-11-01', 'allSaints'],
    ['2026-11-11', 'armistice'],
    ['2026-12-25', 'christmas'],
  ])('names the public holiday on a fixed date: %s', (written, holiday) => {
    expect(publicHolidayOn(day(written), 'national')).toBe(holiday)
  })

  it('keeps the 26th of December for Alsace-Moselle', () => {
    expect(publicHolidayOn(day('2026-12-26'), 'alsaceMoselle')).toBe('saintStephensDay')
  })

  it('names no public holiday on the 26th of December elsewhere', () => {
    expect(publicHolidayOn(day('2026-12-26'), 'national')).toBeNull()
  })

  it.each(EASTER_MONDAYS)('computes Easter Monday: %s', (written) => {
    expect(publicHolidayOn(day(written), 'national')).toBe('easterMonday')
  })

  it.each(ASCENSION_DAYS)('computes Ascension Day: %s', (written) => {
    expect(publicHolidayOn(day(written), 'national')).toBe('ascension')
  })

  it.each(WHIT_MONDAYS)('computes Whit Monday: %s', (written) => {
    expect(publicHolidayOn(day(written), 'national')).toBe('whitMonday')
  })

  it('keeps Good Friday for Alsace-Moselle', () => {
    expect(publicHolidayOn(day('2027-03-26'), 'alsaceMoselle')).toBe('goodFriday')
  })

  it('names no public holiday on Good Friday elsewhere', () => {
    expect(publicHolidayOn(day('2027-03-26'), 'national')).toBeNull()
  })

  it.each([
    ['2026-12-25', 'christmas'],
    ['2027-05-06', 'ascension'],
  ])('keeps the national public holidays for Alsace-Moselle: %s', (written, holiday) => {
    expect(publicHolidayOn(day(written), 'alsaceMoselle')).toBe(holiday)
  })

  it('names no public holiday on a working day', () => {
    expect(publicHolidayOn(day('2026-12-24'), 'national')).toBeNull()
  })
})
