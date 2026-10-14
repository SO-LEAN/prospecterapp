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
