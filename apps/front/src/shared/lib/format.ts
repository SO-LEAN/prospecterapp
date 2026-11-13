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

/**
 * When something is to come, as said from now: "aujourd’hui 14h00", "demain 14h00", then "dans 4 jours", in the
 * time of the browser.
 */
export function formatUpcoming(moment: string, now = new Date()): string {
  const days = Math.round((startOfDay(new Date(moment)) - startOfDay(now)) / DAY_MS)
  const when = relativeDayFormat.format(days, 'day')

  return days <= 1 ? `${when} ${formatHour(moment)}` : when
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
