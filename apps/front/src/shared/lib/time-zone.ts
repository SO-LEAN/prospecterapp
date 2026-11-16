/**
 * "Europe/Paris": the time zone of the browser, the clock its member reads, sent to the API so that a day without an
 * hour, as a to-do is due, is read on it (ADR 0134).
 */
export const readerTimeZone = () => Intl.DateTimeFormat().resolvedOptions().timeZone
