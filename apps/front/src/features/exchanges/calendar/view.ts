import type { View } from './dates'

/** A phone opens the calendar as a list. A wider screen opens it on its month. */
const A_PHONE = '(max-width: 767px)'

export function defaultView(): View {
  return window.matchMedia(A_PHONE).matches ? 'agenda' : 'month'
}
