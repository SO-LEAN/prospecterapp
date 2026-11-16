import type { QueryClient } from '@tanstack/react-query'
import { isProblem } from '@/shared/api/problem'
import { checkedConnectionsQuery, connectionsQuery } from './queries'

/** What the check of my connections could tell: Google out of reach leaves them as last seen. */
export type ConnectionsCheck = 'checked' | 'googleUnreachable'

const isGoogleUnreachable = (failure: unknown) => isProblem(failure) && failure.reason === 'googleUnreachable'

/**
 * Asks each provider whether it still grants the access of my connection, as the page of the connectors opens, then
 * keeps what they answered as the state the banner of the app reads (ADR 0120). While Google does not answer, my
 * connections are read as last seen, so that the section still shows them and lets me disconnect.
 */
export async function checkMyConnections(queryClient: QueryClient): Promise<ConnectionsCheck> {
  try {
    const checked = await queryClient.fetchQuery(checkedConnectionsQuery())
    queryClient.setQueryData(connectionsQuery().queryKey, checked)
    return 'checked'
  } catch (failure) {
    if (!isGoogleUnreachable(failure)) throw failure
    await queryClient.ensureQueryData(connectionsQuery())
    return 'googleUnreachable'
  }
}
