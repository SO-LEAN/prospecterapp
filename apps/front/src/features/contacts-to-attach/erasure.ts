const DAY_MS = 24 * 60 * 60_000

/** From this many days left, the line turns orange, and the first reminder comes (ADR 0146). */
export const SOON_DAYS = 7
/** The day before, it turns red, and the last reminder comes. */
export const IMMINENT_DAYS = 1

export type Urgency = 'later' | 'soon' | 'imminent'

/** The days left until the start of the day of the erasure, one at least while it is to come. */
export function daysBeforeErasure(erasableFrom: string, now: Date): number {
  return Math.max(1, Math.ceil((new Date(erasableFrom).getTime() - now.getTime()) / DAY_MS))
}

export function urgencyOf(days: number): Urgency {
  if (days <= IMMINENT_DAYS) return 'imminent'
  return days <= SOON_DAYS ? 'soon' : 'later'
}
