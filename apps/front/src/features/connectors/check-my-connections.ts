import type { QueryClient } from '@tanstack/react-query'
import { isProblem } from '@/shared/api/problem'
import { checkedConnectionsQuery, connectionsQuery } from './queries'

/** What checking my connections can tell. When Google is out of reach, they stay as last seen. */
export type ConnectionsCheck = 'checked' | 'googleUnreachable'

const isGoogleUnreachable = (failure: unknown) => isProblem(failure) && failure.reason === 'googleUnreachable'

/**
 * Asks each provider whether it still grants my connection's access when the connectors page opens. Then it keeps their
 * answers as the state that the app's banner reads (ADR 0120). While Google does not answer, my connections are read as
 * last seen. So the section still shows them and lets me disconnect.
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
