import { useSuspenseQuery } from '@tanstack/react-query'
import {
  connectionsQuery,
  googleClientQuery,
  googleConnectionOf,
  isGoogleOffered,
  isTheGoogleClientConfigurable,
} from '../queries'
import { GoogleCalendarCard } from './google-calendar-card'
import { GoogleClientCard } from './google-client-card'

/**
 * The services the member connects their accounts at, Google Calendar the one so far, checked as the page opens; for
 * an administrator, the client of Google of the account too.
 */
export function ConnectorsSection() {
  const { data: connections } = useSuspenseQuery(connectionsQuery())

  return (
    <div className="space-y-6">
      <GoogleCalendarCard
        connection={googleConnectionOf(connections)}
        offered={isGoogleOffered(connections)}
      />
      {isTheGoogleClientConfigurable(connections) && <GoogleClientOfTheAccount />}
    </div>
  )
}

function GoogleClientOfTheAccount() {
  const { data: client } = useSuspenseQuery(googleClientQuery())

  return <GoogleClientCard client={client} />
}
