import type { components } from '@/shared/api/schema'
import { type HolidayCalendar, type PublicHoliday, publicHolidayOn } from './public-holidays'
import { type SchoolHolidays, schoolHolidaysOn } from './school-holidays'

type Address = components['schemas']['AddressResource']

/**
 * What a day is worth for selling. It only helps the display: nothing is refused on it. A public holiday on a weekend
 * day stays a weekend day, with the holiday's name.
 */
export type SalesDay =
  | { nature: 'workday' }
  | { nature: 'weekend'; holiday: PublicHoliday | null }
  | { nature: 'publicHoliday'; holiday: PublicHoliday }
  | { nature: 'bridge' }
  | { nature: 'schoolHolidays'; holidays: SchoolHolidays }

const SUNDAY = 0
const MONDAY = 1
const FRIDAY = 5
const SATURDAY = 6

/** Moselle, Bas-Rhin and Haut-Rhin, by the first two digits of their postal codes. */
const ALSACE_MOSELLE_DEPARTMENTS = ['57', '67', '68']

/** The days between a likely bridge and the public holiday it joins to the weekend, by the bridge's weekday. */
const BRIDGED_HOLIDAY_DISTANCE: Record<number, number> = {
  [MONDAY]: 1,
  [FRIDAY]: -1,
}

/** The public holidays that apply to an account, read from its address's postal code. */
export function holidayCalendarOf(address: Address | null | undefined): HolidayCalendar {
  const department = address?.postalCode?.slice(0, 2) ?? ''

  return ALSACE_MOSELLE_DEPARTMENTS.includes(department) ? 'alsaceMoselle' : 'national'
}

/** A day of the sales calendar, in the public holidays' calendar of the account and in zone B. */
export function salesDayOf(day: Date, calendar: HolidayCalendar): SalesDay {
  const holiday = publicHolidayOn(day, calendar)
  if (isWeekend(day)) return { nature: 'weekend', holiday }
  if (holiday) return { nature: 'publicHoliday', holiday }
  if (isLikelyBridge(day, calendar)) return { nature: 'bridge' }

  return workdayOrSchoolHolidays(day)
}

const isWeekend = (day: Date) => [SATURDAY, SUNDAY].includes(day.getDay())

/** The Monday before a public holiday on a Tuesday, or the Friday after a public holiday on a Thursday. */
function isLikelyBridge(day: Date, calendar: HolidayCalendar): boolean {
  const distance = BRIDGED_HOLIDAY_DISTANCE[day.getDay()]

  return distance !== undefined && publicHolidayOn(dayAfter(day, distance), calendar) !== null
}

const dayAfter = (day: Date, days: number) =>
  new Date(day.getFullYear(), day.getMonth(), day.getDate() + days)

function workdayOrSchoolHolidays(day: Date): SalesDay {
  const holidays = schoolHolidaysOn(day)

  return holidays ? { nature: 'schoolHolidays', holidays } : { nature: 'workday' }
}
