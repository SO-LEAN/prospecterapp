import { isoDayOf } from './iso-day'

export type SchoolHolidays = 'allSaints' | 'christmas' | 'winter' | 'spring' | 'ascensionBridge' | 'summer'

/** School holidays from their first day until the day school starts again, that day excluded. */
type Period = { holidays: SchoolHolidays; from: string; until: string }

/**
 * Zone B's school holidays (Strasbourg, Nancy-Metz among others), from data.education.gouv.fr, dataset
 * "fr-en-calendrier-scolaire", checked on 28 November 2026. Update it once a year, when the ministry publishes a new
 * school year.
 * - The 2027 Ascension bridge reads 7 May to 7 May in the dataset. It is written here like the other years: from
 *   Ascension Day to the Monday after.
 * - The 2028 summer holidays are missing: the dataset does not give their end yet.
 */
const ZONE_B: Period[] = [
  { holidays: 'allSaints', from: '2025-10-18', until: '2025-11-03' },
  { holidays: 'christmas', from: '2025-12-20', until: '2026-01-05' },
  { holidays: 'winter', from: '2026-02-14', until: '2026-03-02' },
  { holidays: 'spring', from: '2026-04-11', until: '2026-04-27' },
  { holidays: 'ascensionBridge', from: '2026-05-14', until: '2026-05-18' },
  { holidays: 'summer', from: '2026-07-04', until: '2026-09-01' },
  { holidays: 'allSaints', from: '2026-10-17', until: '2026-11-02' },
  { holidays: 'christmas', from: '2026-12-19', until: '2027-01-04' },
  { holidays: 'winter', from: '2027-02-20', until: '2027-03-08' },
  { holidays: 'spring', from: '2027-04-17', until: '2027-05-03' },
  { holidays: 'ascensionBridge', from: '2027-05-06', until: '2027-05-10' },
  { holidays: 'summer', from: '2027-07-03', until: '2027-09-02' },
  { holidays: 'allSaints', from: '2027-10-23', until: '2027-11-08' },
  { holidays: 'christmas', from: '2027-12-18', until: '2028-01-03' },
  { holidays: 'winter', from: '2028-02-05', until: '2028-02-21' },
  { holidays: 'spring', from: '2028-04-08', until: '2028-04-24' },
  { holidays: 'ascensionBridge', from: '2028-05-25', until: '2028-05-29' },
]

/** Zone B's school holidays a day falls in, or none. */
export function schoolHolidaysOn(day: Date): SchoolHolidays | null {
  const written = isoDayOf(day)

  return ZONE_B.find(({ from, until }) => from <= written && written < until)?.holidays ?? null
}
