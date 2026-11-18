import { api } from '@/shared/api/client'
import { readerTimeZone } from '@/shared/lib/time-zone'

/** My booking page, its inactive periods ended before my day left out. */
export const myBookingPageQuery = () =>
  api.queryOptions('get', '/api/me/booking-page', { params: { query: { timeZone: readerTimeZone() } } })

/** The page a public link leads to, read without signing in. */
export const publicBookingPageQuery = (code: string) =>
  api.queryOptions('get', '/api/booking-pages/{code}', { params: { path: { code } } })
