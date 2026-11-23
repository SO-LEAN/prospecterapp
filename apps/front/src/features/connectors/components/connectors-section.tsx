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

type Props = {
  /** Google did not answer the check when the page opened. The connection is shown as last seen. */
  googleUnreachable?: boolean
}

/**
 * The services that the member connects their accounts at. Google Calendar is the only one so far. The connections
 * are checked when the page opens. An administrator also sees the account's Google client.
 */
export function ConnectorsSection({ googleUnreachable = false }: Props) {
  const { data: connections } = useSuspenseQuery(connectionsQuery())

  return (
    <div className="space-y-6">
      <GoogleCalendarCard
        connection={googleConnectionOf(connections)}
        offered={isGoogleOffered(connections)}
        unchecked={googleUnreachable}
      />
      {isTheGoogleClientConfigurable(connections) && <GoogleClientOfTheAccount />}
    </div>
  )
}

function GoogleClientOfTheAccount() {
  const { data: client } = useSuspenseQuery(googleClientQuery())

  return <GoogleClientCard client={client} />
}
