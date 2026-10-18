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

/** The link that dials a number written with spaces: "+33 3 88 11 22 33" -> "tel:+33388112233" */
export function telUrl(phoneNumber: string): string {
  return `tel:${phoneNumber.replace(/[^\d+]/g, '')}`
}
