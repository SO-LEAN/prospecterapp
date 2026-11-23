/** "Europe/Paris" -> "Paris", "America/New_York" -> "New York": the city that a time zone is named after. */
export function cityOf(timeZone: string): string {
  return (timeZone.split('/').pop() ?? timeZone).replaceAll('_', ' ')
}

/** "2026-11-24T09:00:00+01:00" -> "09:00", on the given time zone's clock. */
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

/** "2026-11-24" -> "mar. 24 nov.": a calendar day, whatever clock it is read on. */
export function dayLabel(day: string): string {
  return dayFormat.format(new Date(`${day}T00:00:00Z`))
}

/** "2026-11-24T09:00:00+01:00" -> "mardi 24 novembre à 9h00": an appointment's time, on the given clock. */
export function appointmentMoment(slot: string, timeZone: string): string {
  const parts = new Intl.DateTimeFormat('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    hour: 'numeric',
    minute: '2-digit',
    timeZone,
  }).formatToParts(new Date(slot))
  const part = (type: Intl.DateTimeFormatPartTypes) => parts.find((each) => each.type === type)?.value
  return `${part('weekday')} ${part('day')} ${part('month')} à ${Number(part('hour'))}h${part('minute')}`
}
