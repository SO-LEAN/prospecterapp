import { createFileRoute } from '@tanstack/react-router'
import { PublicBookingPage } from '@/features/booking/components/public-booking-page'

/**
 * The page that a booking page's public link opens (ADR 0148, 0151), outside the application shell. Anyone with the
 * link opens it without signing in.
 */
export const Route = createFileRoute('/booking/$code')({
  component: BookingPage,
})

function BookingPage() {
  const { code } = Route.useParams()

  return <PublicBookingPage code={code} />
}
