const TAG = /\[[^\]]*\]/g
const LETTER = /\p{Letter}/u

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

const momentFormat = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' })

/** "2026-10-15T12:30:00+00:00" -> "15 oct. 2026, 14:30", in the browser's time zone */
export function formatMoment(moment: string): string {
  return momentFormat.format(new Date(moment))
}

const DAY_MS = 86_400_000
const relativeDayFormat = new Intl.RelativeTimeFormat('fr-FR', { numeric: 'auto' })
const startOfDay = (moment: Date) =>
  new Date(moment.getFullYear(), moment.getMonth(), moment.getDate()).getTime()

/** "2026-11-17T13:00:00+00:00" -> "14h00", in the browser's time zone */
export function formatHour(moment: string): string {
  const date = new Date(moment)

  return `${date.getHours()}h${String(date.getMinutes()).padStart(2, '0')}`
}

const LAST_DAY_SAID_FROM_NOW = 2
const dayFormat = new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' })
const dayOfAnotherYearFormat = new Intl.DateTimeFormat('fr-FR', {
  weekday: 'short',
  day: 'numeric',
  month: 'short',
  year: 'numeric',
})

/** "jeu. 26 nov.", with the year when it is not the current one ("jeu. 7 janv. 2027"), in the browser's time zone */
export function formatDay(date: Date, now: Date): string {
  return (date.getFullYear() === now.getFullYear() ? dayFormat : dayOfAnotherYearFormat).format(date)
}

/**
 * When something upcoming happens, with its hour, in the browser's time zone: "aujourd’hui 14h00", "demain 14h00",
 * "après-demain 14h00". Later dates show the date, so that the reader does not count the days: "lun. 30 nov., 9h00".
 * The year shows when it is not the current one: "jeu. 7 janv. 2027, 9h00".
 */
export function formatUpcoming(moment: string, now = new Date()): string {
  const date = new Date(moment)
  const days = Math.round((startOfDay(date) - startOfDay(now)) / DAY_MS)

  return days <= LAST_DAY_SAID_FROM_NOW
    ? `${relativeDayFormat.format(days, 'day')} ${formatHour(moment)}`
    : `${formatDay(date, now)}, ${formatHour(moment)}`
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
