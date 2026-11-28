import { describe, expect, it } from 'vitest'
import { schoolHolidaysOn } from './school-holidays'

const day = (written: string) => new Date(`${written}T00:00`)

/** Zone B's school holidays from data.education.gouv.fr: their first day, their last one and the day school starts again. */
const ZONE_B = [
  ['allSaints', '2025-10-18', '2025-11-02', '2025-11-03'],
  ['christmas', '2025-12-20', '2026-01-04', '2026-01-05'],
  ['winter', '2026-02-14', '2026-03-01', '2026-03-02'],
  ['spring', '2026-04-11', '2026-04-26', '2026-04-27'],
  ['ascensionBridge', '2026-05-14', '2026-05-17', '2026-05-18'],
  ['summer', '2026-07-04', '2026-08-31', '2026-09-01'],
  ['allSaints', '2026-10-17', '2026-11-01', '2026-11-02'],
  ['christmas', '2026-12-19', '2027-01-03', '2027-01-04'],
  ['winter', '2027-02-20', '2027-03-07', '2027-03-08'],
  ['spring', '2027-04-17', '2027-05-02', '2027-05-03'],
  ['ascensionBridge', '2027-05-06', '2027-05-09', '2027-05-10'],
  ['summer', '2027-07-03', '2027-09-01', '2027-09-02'],
  ['allSaints', '2027-10-23', '2027-11-07', '2027-11-08'],
  ['christmas', '2027-12-18', '2028-01-02', '2028-01-03'],
  ['winter', '2028-02-05', '2028-02-20', '2028-02-21'],
  ['spring', '2028-04-08', '2028-04-23', '2028-04-24'],
  ['ascensionBridge', '2028-05-25', '2028-05-28', '2028-05-29'],
] as const

describe('schoolHolidaysOn', () => {
  it('names the school holidays a day falls in', () => {
    expect(schoolHolidaysOn(day('2026-12-21'))).toBe('christmas')
  })

  it('starts the school holidays on their first day', () => {
    expect(schoolHolidaysOn(day('2026-12-19'))).toBe('christmas')
  })

  it('ends the school holidays before the day school starts again', () => {
    expect(schoolHolidaysOn(day('2027-01-04'))).toBeNull()
  })

  it.each(ZONE_B)('names the %s holidays from %s', (holidays, first) => {
    expect(schoolHolidaysOn(day(first))).toBe(holidays)
  })

  it.each(ZONE_B)('names the %s holidays until %s', (holidays, _first, last) => {
    expect(schoolHolidaysOn(day(last))).toBe(holidays)
  })

  it.each(ZONE_B)(
    'names no school holidays once the %s holidays from %s end',
    (_holidays, _first, _last, back) => {
      expect(schoolHolidaysOn(day(back))).toBeNull()
    },
  )

  it('names no school holidays before the first ones it knows', () => {
    expect(schoolHolidaysOn(day('2025-10-17'))).toBeNull()
  })
})
