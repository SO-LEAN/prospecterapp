/**
 * The pauses before each reading again of a video call awaiting the link its calendar creates (ADR 0137), a few
 * seconds after it is planned: a few readings, slower and slower, never a steady polling of the API.
 */
const PAUSES_MS = [1_000, 2_000, 4_000, 8_000, 15_000] as const

/** After its nth reading, how long before reading it again, or never once the link came or the last pause passed. */
export const pauseBeforeReadingAgain = (linkAwaited: boolean, readings: number): number | false =>
  linkAwaited ? (PAUSES_MS[readings - 1] ?? false) : false
