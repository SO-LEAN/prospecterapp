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

/** The link that dials a number written with spaces: "+33 3 88 11 22 33" -> "tel:+33388112233" */
export function telUrl(phoneNumber: string): string {
  return `tel:${phoneNumber.replace(/[^\d+]/g, '')}`
}
