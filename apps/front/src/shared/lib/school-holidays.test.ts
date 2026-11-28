import { describe, expect, it } from 'vitest'
import { schoolHolidaysOn } from './school-holidays'

const day = (written: string) => new Date(`${written}T00:00`)

/** Zone B's school holidays from data.education.gouv.fr: their first day and their last one. */
const ZONE_B = [
  ['allSaints', '2025-10-18', '2025-11-02'],
  ['christmas', '2025-12-20', '2026-01-04'],
  ['winter', '2026-02-14', '2026-03-01'],
  ['spring', '2026-04-11', '2026-04-26'],
  ['ascensionBridge', '2026-05-14', '2026-05-17'],
  ['summer', '2026-07-04', '2026-08-31'],
  ['allSaints', '2026-10-17', '2026-11-01'],
  ['christmas', '2026-12-19', '2027-01-03'],
  ['winter', '2027-02-20', '2027-03-07'],
  ['spring', '2027-04-17', '2027-05-02'],
  ['ascensionBridge', '2027-05-06', '2027-05-09'],
  ['summer', '2027-07-03', '2027-09-01'],
  ['allSaints', '2027-10-23', '2027-11-07'],
  ['christmas', '2027-12-18', '2028-01-02'],
  ['winter', '2028-02-05', '2028-02-20'],
  ['spring', '2028-04-08', '2028-04-23'],
  ['ascensionBridge', '2028-05-25', '2028-05-28'],
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
})
