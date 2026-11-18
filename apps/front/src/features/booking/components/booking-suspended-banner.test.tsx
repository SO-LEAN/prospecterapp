import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import type { BookingPage } from '../mutations'
import { myBookingPageQuery } from '../queries'
import { BookingSuspendedBanner } from './booking-suspended-banner'

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const BANNER = 'Votre lien de prise de rendez-vous est suspendu : reconnectez Google'

const link = (href: string, method: string) => ({ href, method })

const myPage = (suspended: boolean): BookingPage => ({
  open: true,
  windows: [],
  timeZone: 'Europe/Paris',
  inactivePeriods: [],
  channels: ['call'],
  code: 'AbCdEfGhIjKlMnOpQrStUv',
  calendarConnected: !suspended,
  suspended,
  _links: {
    self: link('/api/me/booking-page', 'GET'),
    setWindows: link('/api/me/booking-page/windows', 'PATCH'),
    addInactivePeriod: link('/api/me/booking-page/inactive-periods', 'POST'),
    offerChannels: link('/api/me/booking-page/channels', 'PATCH'),
    close: link('/api/me/booking-page/opening', 'DELETE'),
  },
})

async function renderTheBanner(known: BookingPage) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(myBookingPageQuery().queryKey, known)
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <BookingSuspendedBanner />
    </QueryClientProvider>,
  )
}

describe('BookingSuspendedBanner', () => {
  it('says above the page that my link is suspended, linking to the connectors', async () => {
    await renderTheBanner(myPage(true))

    expect(screen.getByRole('status')).toHaveTextContent(BANNER)
    expect(screen.getByRole('link', { name: 'Reconnecter' })).toHaveAttribute(
      'href',
      '/settings?section=connectors',
    )
  })

  it('says nothing while my link offers its slots', async () => {
    await renderTheBanner(myPage(false))

    expect(screen.queryByRole('status')).not.toBeInTheDocument()
  })
})
