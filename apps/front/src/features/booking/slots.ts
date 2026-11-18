/** "Europe/Paris" -> "Paris", "America/New_York" -> "New York": the city a time zone is named after. */
export function cityOf(timeZone: string): string {
  return (timeZone.split('/').pop() ?? timeZone).replaceAll('_', ' ')
}

/** "2026-11-24T09:00:00+01:00" -> "09:00", on the clock of the given time zone. */
export function slotTime(slot: string, timeZone: string): string {
  return new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit', timeZone }).format(
    new Date(slot),
  )
}

const dayFormat = new Intl.DateTimeFormat('fr-FR', {
  weekday: 'short',
  day: 'numeric',
  month: 'short',
  timeZone: 'UTC',
})

/** "2026-11-24" -> "mar. 24 nov.": a day of the calendar, whatever the clock it is read on. */
export function dayLabel(day: string): string {
  return dayFormat.format(new Date(`${day}T00:00:00Z`))
}
