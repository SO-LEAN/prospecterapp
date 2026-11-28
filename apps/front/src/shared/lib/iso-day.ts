/** A day on the browser's clock, in ISO 8601 date format: "2026-12-25". */
export const isoDayOf = (day: Date) =>
  `${day.getFullYear()}-${twoDigits(day.getMonth() + 1)}-${twoDigits(day.getDate())}`

const twoDigits = (value: number) => String(value).padStart(2, '0')
