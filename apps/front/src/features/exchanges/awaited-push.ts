/**
 * The pauses before each reading again of an exchange awaiting what its push into the calendar of its holder brings
 * (ADR 0137), a few seconds after it is planned: the link of a video call, or its state synced. A few readings, slower
 * and slower, never a steady polling of the API.
 */
const PAUSES_MS = [1_000, 2_000, 4_000, 8_000, 15_000] as const

/** After its nth reading, how long before reading it again, or never once it came or the last pause passed. */
export const pauseBeforeReadingAgain = (awaited: boolean, readings: number): number | false =>
  awaited ? (PAUSES_MS[readings - 1] ?? false) : false
