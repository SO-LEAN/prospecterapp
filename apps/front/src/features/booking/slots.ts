/** "Europe/Paris" -> "Paris", "America/New_York" -> "New York": the city that a time zone is named after. */
export function cityOf(timeZone: string): string {
  return (timeZone.split('/').pop() ?? timeZone).replaceAll('_', ' ')
}
