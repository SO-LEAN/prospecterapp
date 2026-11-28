import { describe, expect, it } from 'vitest'
import { holidayCalendarOf, salesDayOf } from './sales-calendar'

const day = (written: string) => new Date(`${written}T00:00`)

const accountIn = (postalCode: string) => ({ street: null, postalCode, city: null, country: 'FR' })

const STRASBOURG = accountIn('67000')
const METZ = accountIn('57000')
const MULHOUSE = accountIn('68100')
const LYON = accountIn('69001')
const PARIS = accountIn('75001')

describe('holidayCalendarOf', () => {
  it.each([
    ['Metz', METZ],
    ['Strasbourg', STRASBOURG],
    ['Mulhouse', MULHOUSE],
  ])("keeps Alsace-Moselle's public holidays for an account in %s", (_city, address) => {
    expect(holidayCalendarOf(address)).toBe('alsaceMoselle')
  })

  it('keeps the national public holidays elsewhere', () => {
    expect(holidayCalendarOf(LYON)).toBe('national')
  })

  it('keeps the national public holidays for an account without an address', () => {
    expect(holidayCalendarOf(null)).toBe('national')
  })

  it('keeps the national public holidays for an address without a postal code', () => {
    expect(holidayCalendarOf({ street: null, postalCode: null, city: 'Strasbourg', country: 'FR' })).toBe(
      'national',
    )
  })
})

describe('salesDayOf', () => {
  it('names the public holiday a day is', () => {
    expect(salesDayOf(day('2026-12-25'), holidayCalendarOf(STRASBOURG))).toEqual({
      nature: 'publicHoliday',
      holiday: 'christmas',
    })
  })

  it.each([
    ['Ascension Day 2027, in Strasbourg', '2027-05-07', STRASBOURG],
    ['Ascension Day 2026, in Paris', '2026-05-15', PARIS],
  ])(
    'sees a likely bridge on the Friday after a public holiday on a Thursday: %s',
    (_holiday, written, address) => {
      expect(salesDayOf(day(written), holidayCalendarOf(address))).toEqual({ nature: 'bridge' })
    },
  )

  it('sees a likely bridge on the Monday before a public holiday on a Tuesday', () => {
    expect(salesDayOf(day('2026-07-13'), holidayCalendarOf(PARIS))).toEqual({ nature: 'bridge' })
  })

  it("names zone B's school holidays a working day falls in", () => {
    expect(salesDayOf(day('2026-12-21'), holidayCalendarOf(STRASBOURG))).toEqual({
      nature: 'schoolHolidays',
      holidays: 'christmas',
    })
  })

  it('keeps the day school starts again a working day', () => {
    expect(salesDayOf(day('2027-01-04'), holidayCalendarOf(STRASBOURG))).toEqual({ nature: 'workday' })
  })

  it('sees no bridge on the day before a public holiday on a Thursday', () => {
    expect(salesDayOf(day('2027-05-05'), holidayCalendarOf(PARIS))).toEqual({ nature: 'workday' })
  })

  it("keeps Alsace-Moselle's Good Friday a working day elsewhere", () => {
    expect(salesDayOf(day('2027-03-26'), holidayCalendarOf(LYON))).toEqual({ nature: 'workday' })
  })

  it("names Alsace-Moselle's Good Friday for an account in Metz", () => {
    expect(salesDayOf(day('2027-03-26'), holidayCalendarOf(METZ))).toEqual({
      nature: 'publicHoliday',
      holiday: 'goodFriday',
    })
  })

  it.each([
    ['Saturday', '2026-12-26'],
    ['Sunday', '2026-12-27'],
  ])('keeps a %s a weekend day', (_weekday, written) => {
    expect(salesDayOf(day(written), holidayCalendarOf(PARIS))).toEqual({ nature: 'weekend', holiday: null })
  })

  it('keeps a public holiday on a Saturday a weekend day, with its name', () => {
    expect(salesDayOf(day('2026-08-15'), holidayCalendarOf(PARIS))).toEqual({
      nature: 'weekend',
      holiday: 'assumption',
    })
  })

  it("names Alsace-Moselle's Saint Stephen's Day on a weekend day", () => {
    expect(salesDayOf(day('2026-12-26'), holidayCalendarOf(STRASBOURG))).toEqual({
      nature: 'weekend',
      holiday: 'saintStephensDay',
    })
  })
})
