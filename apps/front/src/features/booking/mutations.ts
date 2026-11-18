import { useQueryClient } from '@tanstack/react-query'
import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { myBookingPageQuery } from './queries'

export type BookingPage = components['schemas']['BookingPageResource']
export type Window = components['schemas']['WindowResource']

/** Every gesture answers my page as it now is: kept as the page read, refreshed on my clock. */
function useKeepMyPage() {
  const queryClient = useQueryClient()

  return (page: BookingPage) => {
    queryClient.setQueryData(myBookingPageQuery().queryKey, page)
    void queryClient.invalidateQueries({ queryKey: myBookingPageQuery().queryKey })
  }
}

export function useSetWindows() {
  return api.useMutation('patch', '/api/me/booking-page/windows', { onSuccess: useKeepMyPage() })
}

export function useAddInactivePeriod() {
  return api.useMutation('post', '/api/me/booking-page/inactive-periods', { onSuccess: useKeepMyPage() })
}

export function useRemoveInactivePeriod() {
  return api.useMutation('delete', '/api/me/booking-page/inactive-periods/{id}', {
    onSuccess: useKeepMyPage(),
  })
}

export function useOfferChannels() {
  return api.useMutation('patch', '/api/me/booking-page/channels', { onSuccess: useKeepMyPage() })
}

export function useOpenBooking() {
  return api.useMutation('post', '/api/me/booking-page/opening', { onSuccess: useKeepMyPage() })
}

export function useCloseBooking() {
  return api.useMutation('delete', '/api/me/booking-page/opening', { onSuccess: useKeepMyPage() })
}

export function useChangeBookingLink() {
  return api.useMutation('post', '/api/me/booking-page/link', { onSuccess: useKeepMyPage() })
}
