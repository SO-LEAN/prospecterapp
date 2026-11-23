/**
 * "Europe/Paris": the browser's time zone, the clock its member reads. The SPA sends it to the API, which reads a day
 * without an hour, such as a to-do's due date, in that zone (ADR 0134).
 */
export const readerTimeZone = () => Intl.DateTimeFormat().resolvedOptions().timeZone
