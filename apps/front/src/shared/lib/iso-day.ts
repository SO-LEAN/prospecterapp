/** "2026-12-25": a day on the browser's clock, written as ISO 8601 writes a date. */
export const isoDayOf = (day: Date) =>
  `${day.getFullYear()}-${twoDigits(day.getMonth() + 1)}-${twoDigits(day.getDate())}`

const twoDigits = (value: number) => String(value).padStart(2, '0')
