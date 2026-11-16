/** "Ada Lovelace" -> "AL" */
export function initials(name: string): string {
  return (
    name
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((word) => word[0]?.toUpperCase())
      .join('') || '?'
  )
}

const numberFormat = new Intl.NumberFormat('fr-FR')

export function formatNumber(value: number): string {
  return numberFormat.format(value)
}

const momentFormat = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' })

/** "2026-10-15T12:30:00+00:00" -> "15 oct. 2026, 14:30", in the time of the browser */
export function formatMoment(moment: string): string {
  return momentFormat.format(new Date(moment))
}

const DAY_MS = 86_400_000
const relativeDayFormat = new Intl.RelativeTimeFormat('fr-FR', { numeric: 'auto' })
const startOfDay = (moment: Date) =>
  new Date(moment.getFullYear(), moment.getMonth(), moment.getDate()).getTime()

/** "2026-11-17T13:00:00+00:00" -> "14h00", in the time of the browser */
export function formatHour(moment: string): string {
  const date = new Date(moment)

  return `${date.getHours()}h${String(date.getMinutes()).padStart(2, '0')}`
}

const LAST_DAY_SAID_FROM_NOW = 2
const dayFormat = new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' })

/**
 * When something is to come, with its hour, in the time of the browser: "aujourd’hui 14h00", "demain 14h00",
 * "après-demain 14h00", then its date, read without counting the days: "lun. 30 nov., 9h00".
 */
export function formatUpcoming(moment: string, now = new Date()): string {
  const date = new Date(moment)
  const days = Math.round((startOfDay(date) - startOfDay(now)) / DAY_MS)

  return days <= LAST_DAY_SAID_FROM_NOW
    ? `${relativeDayFormat.format(days, 'day')} ${formatHour(moment)}`
    : `${dayFormat.format(date)}, ${formatHour(moment)}`
}

/** The link that dials a number written with spaces: "+33 3 88 11 22 33" -> "tel:+33388112233" */
export function telUrl(phoneNumber: string): string {
  return `tel:${phoneNumber.replace(/[^\d+]/g, '')}`
}

const sizeFormat = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 })
const UNITS = ['octets', 'Ko', 'Mo', 'Go'] as const
const KILO = 1024

/** A size in bytes as a person reads it: 2048 -> "2 Ko", 1572864 -> "1,5 Mo" */
export function formatSize(bytes: number): string {
  const power = Math.min(Math.floor(Math.log(Math.max(bytes, 1)) / Math.log(KILO)), UNITS.length - 1)

  return `${sizeFormat.format(bytes / KILO ** power)} ${UNITS[power]}`
}
