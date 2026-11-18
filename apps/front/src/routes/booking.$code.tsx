import { createFileRoute } from '@tanstack/react-router'
import { PublicBookingPage } from '@/features/booking/components/public-booking-page'

/**
 * The page a public link of a booking leads to (ADR 0148, 0151), outside the shell of the application: whomever holds
 * the link opens it without signing in.
 */
export const Route = createFileRoute('/booking/$code')({
  component: BookingPage,
})

function BookingPage() {
  const { code } = Route.useParams()

  return <PublicBookingPage code={code} />
}
