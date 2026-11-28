/** France's public holidays. Alsace-Moselle adds Good Friday and Saint Stephen's Day to the national ones. */
export type HolidayCalendar = 'national' | 'alsaceMoselle'

export type PublicHoliday =
  | 'newYearsDay'
  | 'goodFriday'
  | 'easterMonday'
  | 'ascension'
  | 'whitMonday'
  | 'labourDay'
  | 'victoryInEurope'
  | 'bastilleDay'
  | 'assumption'
  | 'allSaints'
  | 'armistice'
  | 'christmas'
  | 'saintStephensDay'

const ON_A_FIXED_DATE: Record<string, PublicHoliday> = {
  '01-01': 'newYearsDay',
  '05-01': 'labourDay',
  '05-08': 'victoryInEurope',
  '07-14': 'bastilleDay',
  '08-15': 'assumption',
  '11-01': 'allSaints',
  '11-11': 'armistice',
  '12-25': 'christmas',
}

const IN_ALSACE_MOSELLE_ON_A_FIXED_DATE: Record<string, PublicHoliday> = {
  ...ON_A_FIXED_DATE,
  '12-26': 'saintStephensDay',
}

const FIXED_DATES_OF: Record<HolidayCalendar, Record<string, PublicHoliday>> = {
  national: ON_A_FIXED_DATE,
  alsaceMoselle: IN_ALSACE_MOSELLE_ON_A_FIXED_DATE,
}

/** The movable feasts, by the number of days between Easter Sunday and them. */
const AFTER_EASTER: Record<number, PublicHoliday> = {
  1: 'easterMonday',
  39: 'ascension',
  50: 'whitMonday',
}

const IN_ALSACE_MOSELLE_AFTER_EASTER: Record<number, PublicHoliday> = {
  ...AFTER_EASTER,
  [-2]: 'goodFriday',
}

const MOVABLE_FEASTS_OF: Record<HolidayCalendar, Record<number, PublicHoliday>> = {
  national: AFTER_EASTER,
  alsaceMoselle: IN_ALSACE_MOSELLE_AFTER_EASTER,
}

const DAY_IN_MILLISECONDS = 24 * 60 * 60 * 1000

/** The public holiday on a day, in the calendar of the account's region, or none. */
export function publicHolidayOn(day: Date, calendar: HolidayCalendar): PublicHoliday | null {
  return (
    FIXED_DATES_OF[calendar][monthAndDateOf(day)] ?? MOVABLE_FEASTS_OF[calendar][daysAfterEaster(day)] ?? null
  )
}

/** "12-25": a day's month and date, the key of the public holidays on a fixed date. */
const monthAndDateOf = (day: Date) => `${twoDigits(day.getMonth() + 1)}-${twoDigits(day.getDate())}`

const twoDigits = (value: number) => String(value).padStart(2, '0')

function daysAfterEaster(day: Date): number {
  return (utcMidnightOf(day) - easterSundayOf(day.getFullYear())) / DAY_IN_MILLISECONDS
}

const utcMidnightOf = (day: Date) => Date.UTC(day.getFullYear(), day.getMonth(), day.getDate())

/** Easter Sunday of a Gregorian year, at midnight UTC, by Meeus's algorithm (Astronomical Algorithms, chapter 8). */
function easterSundayOf(year: number): number {
  const a = year % 19
  const b = Math.floor(year / 100)
  const c = year % 100
  const d = Math.floor(b / 4)
  const e = b % 4
  const f = Math.floor((b + 8) / 25)
  const g = Math.floor((b - f + 1) / 3)
  const h = (19 * a + b - d - g + 15) % 30
  const i = Math.floor(c / 4)
  const k = c % 4
  const l = (32 + 2 * e + 2 * i - h - k) % 7
  const m = Math.floor((a + 11 * h + 22 * l) / 451)
  const month = Math.floor((h + l - 7 * m + 114) / 31)
  const date = ((h + l - 7 * m + 114) % 31) + 1

  return Date.UTC(year, month - 1, date)
}
