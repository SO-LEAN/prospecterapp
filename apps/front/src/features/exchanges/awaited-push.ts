/**
 * The pauses before each new reading of an exchange that awaits the result of its push into its holder's calendar
 * (ADR 0137). The result comes a few seconds after the exchange is planned: a video call's link, or its synced state.
 * There are a few readings, slower and slower, never a steady polling of the API.
 */
const PAUSES_MS = [1_000, 2_000, 4_000, 8_000, 15_000] as const

/** After the nth reading, how long to wait before the next one. It is never once the result has come or the last pause has passed. */
export const pauseBeforeReadingAgain = (awaited: boolean, readings: number): number | false =>
  awaited ? (PAUSES_MS[readings - 1] ?? false) : false
