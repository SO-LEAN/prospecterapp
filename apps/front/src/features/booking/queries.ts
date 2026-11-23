import { api } from '@/shared/api/client'
import { readerTimeZone } from '@/shared/lib/time-zone'

/** My booking page, without the inactive periods that ended before my current day. */
export const myBookingPageQuery = () =>
  api.queryOptions('get', '/api/me/booking-page', { params: { query: { timeZone: readerTimeZone() } } })

/** The page that a public link leads to, read without signing in. */
export const publicBookingPageQuery = (code: string) =>
  api.queryOptions('get', '/api/booking-pages/{code}', { params: { path: { code } } })
