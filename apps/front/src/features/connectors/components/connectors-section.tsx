import { useSuspenseQuery } from '@tanstack/react-query'
import { connectionsQuery, googleConnectionOf } from '../queries'
import { GoogleCalendarCard } from './google-calendar-card'

/** The services the member connects their accounts at, Google Calendar the one so far, checked as the page opens. */
export function ConnectorsSection() {
  const { data: connections } = useSuspenseQuery(connectionsQuery())

  return <GoogleCalendarCard connection={googleConnectionOf(connections)} />
}
