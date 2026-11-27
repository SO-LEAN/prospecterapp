const TAG = /\[[^\]]*\]/g
const LETTER = /\p{L}/u

/** "Ada Lovelace" -> "AL", "[TEST] Paul Martin" -> "PM", "3M France" -> "MF" */
export function initials(name: string): string {
  return (
    name
      .replace(TAG, ' ')
      .split(/\s+/)
      .map((word) => word.match(LETTER)?.[0])
      .filter((letter) => letter !== undefined)
      .slice(0, 2)
      .join('')
      .toUpperCase() || '?'
  )
}

const numberFormat = new Intl.NumberFormat('fr-FR')

export function formatNumber(value: number): string {
  return numberFormat.format(value)
}

// Every date and hour the SPA shows is written by one of the functions below, in one way for each case
// (docs/architecture/front.md, "Dates and hours"). They read the browser's clock unless a time zone is given.

/**
 * "jeu. 26 nov.", with the year when it is not the current one ("jeu. 7 janv. 2027"). It reads the browser's clock, or
 * the clock of the time zone given.
 */
export function formatDay(date: Date, now = new Date(), timeZone?: string): string {
  const { weekday, day, month, year } = clockOf(date, timeZone)
  const shown = `${weekday} ${day} ${month}`

  return year === clockOf(now, timeZone).year ? shown : `${shown} ${year}`
}

/**
 * "2026-11-24" -> "mar. 24 nov.", and with "08:30" -> "mar. 24 nov. · 8h30": a day and an hour kept as written, such as
 * a to-do's due day, whatever the time zone (ADR 0134)
 */
export function formatCalendarDay(day: string, now = new Date(), time?: string | null): string {
  const [year = 0, month = 1, dayOfMonth = 1] = day.split('-').map(Number)
  const shownDay = formatDay(new Date(year, month - 1, dayOfMonth), now)

  return time ? `${shownDay} · ${formatClockTime(time)}` : shownDay
}

/** "2026-11-17T13:00:00+00:00" -> "14h00", on the browser's clock or on the clock of the time zone given */
export function formatHour(moment: string, timeZone?: string): string {
  const { hour, minute } = clockOf(new Date(moment), timeZone)

  return hourAndMinutes(Number(hour), Number(minute))
}

/** "08:30" -> "8h30": an hour kept as written, such as a to-do's, whatever the time zone */
export function formatClockTime(time: string): string {
  const [hours = 0, minutes = 0] = time.split(':').map(Number)

  return hourAndMinutes(hours, minutes)
}

const hourAndMinutes = (hours: number, minutes: number) => `${hours}h${String(minutes).padStart(2, '0')}`

/**
 * "2026-11-17T10:00:00+00:00" -> "mar. 17 nov. · 11h00", with the year when it is not the current one. It reads the
 * browser's clock, or the clock of the time zone given.
 */
export function formatMoment(moment: string, now = new Date(), timeZone?: string): string {
  return `${formatDay(new Date(moment), now, timeZone)} · ${formatHour(moment, timeZone)}`
}

/** "ven. 20 nov. · 16h15 à 16h45", and both days when it ends on another one: "… à sam. 21 nov. · 9h00" */
export function formatRange(start: string, end: string, now = new Date()): string {
  const sameDay = startOfDay(new Date(start)) === startOfDay(new Date(end))

  return `${formatMoment(start, now)} à ${sameDay ? formatHour(end) : formatMoment(end, now)}`
}

const LAST_DAY_SAID_FROM_NOW = 2
const relativeDayFormat = new Intl.RelativeTimeFormat('fr-FR', { numeric: 'auto' })

/**
 * When something upcoming happens: its date and hour, with the day said from now as a complement until the day after
 * tomorrow. "jeu. 26 nov. · 14h00 (demain)", "lun. 30 nov. · 9h00".
 */
export function formatUpcoming(moment: string, now = new Date()): string {
  const days = Math.round((startOfDay(new Date(moment)) - startOfDay(now)) / DAY_MS)
  const absolute = formatMoment(moment, now)

  return days >= 0 && days <= LAST_DAY_SAID_FROM_NOW
    ? `${absolute} (${relativeDayFormat.format(days, 'day')})`
    : absolute
}

const MINUTE_MS = 60_000
const MINUTES_IN_AN_HOUR = 60
const timeLeftFormat = new Intl.RelativeTimeFormat('fr-FR', { style: 'short' })

/** "dans 25 min", "dans 2 h": the time left before something starts, beside its moment. Nothing once it has started. */
export function formatTimeLeft(moment: string, now: Date): string | null {
  const minutes = Math.ceil((new Date(moment).getTime() - now.getTime()) / MINUTE_MS)

  if (minutes <= 0) return null

  return minutes < MINUTES_IN_AN_HOUR
    ? timeLeftFormat.format(minutes, 'minute')
    : timeLeftFormat.format(Math.floor(minutes / MINUTES_IN_AN_HOUR), 'hour')
}

const fullDayFormat = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' })

/** "Mardi 17 novembre 2026": a day heading a page or a list */
export function formatDayTitle(day: Date): string {
  return capitalized(formatFullDay(day))
}

/** "mardi 17 novembre 2026": a day written whole in a sentence, or in the accessible name of a day */
export function formatFullDay(day: Date): string {
  return fullDayFormat.format(day)
}

const dayOfMonthFormat = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short' })
const dayOfMonthAndYearFormat = new Intl.DateTimeFormat('fr-FR', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
})

/** "16 au 22 nov. 2026 · S47": a week from its first day to its last one, with its ISO number */
export function formatWeekTitle(first: Date, last: Date): string {
  return `${firstDayOfTheWeek(first, last)} au ${dayOfMonthAndYearFormat.format(last)} · S${isoWeekOf(first)}`
}

/** "16", "30 nov." when the week ends in another month, "28 déc. 2026" in another year: what the last day does not say */
function firstDayOfTheWeek(first: Date, last: Date): string {
  if (first.getFullYear() !== last.getFullYear()) return dayOfMonthAndYearFormat.format(first)
  if (first.getMonth() !== last.getMonth()) return dayOfMonthFormat.format(first)
  return String(first.getDate())
}

const THURSDAY = 4
const DAYS_IN_A_WEEK = 7

/** The ISO 8601 number of the week a day is in: the week holding the year's first Thursday is the first one. */
function isoWeekOf(day: Date): number {
  const thursday = new Date(
    day.getFullYear(),
    day.getMonth(),
    day.getDate() + THURSDAY - (day.getDay() || DAYS_IN_A_WEEK),
  )
  const firstOfItsYear = new Date(thursday.getFullYear(), 0, 1)
  const daysIntoItsYear = Math.round((startOfDay(thursday) - startOfDay(firstOfItsYear)) / DAY_MS)

  return Math.floor(daysIntoItsYear / DAYS_IN_A_WEEK) + 1
}

const monthTitleFormat = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' })

/** "Novembre 2026": the month a day is in, heading a calendar */
export function formatMonthTitle(day: Date): string {
  return capitalized(monthTitleFormat.format(day))
}

/** "mar.": the weekday heading a column of days */
export function formatWeekday(day: Date): string {
  return clockOf(day).weekday
}

const capitalized = (text: string) => text.charAt(0).toUpperCase() + text.slice(1)

const DAY_MS = 86_400_000
const startOfDay = (moment: Date) =>
  new Date(moment.getFullYear(), moment.getMonth(), moment.getDate()).getTime()

type Clock = Record<'weekday' | 'day' | 'month' | 'year' | 'hour' | 'minute', string>

const clockFormats = new Map<string | undefined, Intl.DateTimeFormat>()

/** What a clock reads at a moment, in French: the browser's clock, or the clock of the time zone given. */
function clockOf(moment: Date, timeZone?: string): Clock {
  const parts = clockFormatOf(timeZone).formatToParts(moment)
  const part = (type: keyof Clock) => parts.find((each) => each.type === type)?.value ?? ''

  return {
    weekday: part('weekday'),
    day: part('day'),
    month: part('month'),
    year: part('year'),
    hour: part('hour'),
    minute: part('minute'),
  }
}

function clockFormatOf(timeZone: string | undefined): Intl.DateTimeFormat {
  const known = clockFormats.get(timeZone)
  if (known) return known
  const format = new Intl.DateTimeFormat('fr-FR', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hourCycle: 'h23',
    timeZone,
  })
  clockFormats.set(timeZone, format)
  return format
}

/** The link that dials a number written with spaces: "+33 3 88 11 22 33" -> "tel:+33388112233" */
export function telUrl(phoneNumber: string): string {
  return `tel:${phoneNumber.replace(/[^\d+]/g, '')}`
}

const sizeFormat = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 })
const UNITS = ['octets', 'Ko', 'Mo', 'Go'] as const
const KILO = 1024

/** A size in bytes, as a person reads it: 2048 -> "2 Ko", 1572864 -> "1,5 Mo" */
export function formatSize(bytes: number): string {
  const power = Math.min(Math.floor(Math.log(Math.max(bytes, 1)) / Math.log(KILO)), UNITS.length - 1)

  return `${sizeFormat.format(bytes / KILO ** power)} ${UNITS[power]}`
}
