const DAY_MS = 24 * 60 * 60_000

/** With this many days left, the line turns orange and the first reminder comes (ADR 0146). */
export const SOON_DAYS = 7
/** On the day before, the line turns red and the last reminder comes. */
export const IMMINENT_DAYS = 1

export type Urgency = 'later' | 'soon' | 'imminent'

/** The days left until the start of the erasure's day. It is at least one while the erasure is still to come. */
export function daysBeforeErasure(erasableFrom: string, now: Date): number {
  return Math.max(1, Math.ceil((new Date(erasableFrom).getTime() - now.getTime()) / DAY_MS))
}

export function urgencyOf(days: number): Urgency {
  if (days <= IMMINENT_DAYS) return 'imminent'
  return days <= SOON_DAYS ? 'soon' : 'later'
}
