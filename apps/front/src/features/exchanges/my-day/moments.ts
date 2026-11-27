import type { Exchange } from '../queries'

const MINUTE_MS = 60_000

/** From its start until the end of its duration. An exchange without a duration is never in progress. */
export function isInProgress(
  { occurredAt, durationInMinutes }: Pick<Exchange, 'occurredAt' | 'durationInMinutes'>,
  now: Date,
): boolean {
  const start = new Date(occurredAt).getTime()
  const end = start + (durationInMinutes ?? 0) * MINUTE_MS

  return start <= now.getTime() && now.getTime() < end
}
