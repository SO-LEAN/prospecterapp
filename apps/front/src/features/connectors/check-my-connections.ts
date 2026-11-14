import type { QueryClient } from '@tanstack/react-query'
import { checkedConnectionsQuery, connectionsQuery } from './queries'

/**
 * Asks each provider whether it still grants the access of my connection, as the page of the connectors opens, then
 * keeps what they answered as the state the banner of the app reads (ADR 0120).
 */
export async function checkMyConnections(queryClient: QueryClient) {
  const checked = await queryClient.fetchQuery(checkedConnectionsQuery())
  queryClient.setQueryData(connectionsQuery().queryKey, checked)
}
