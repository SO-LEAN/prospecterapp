import { useQueryClient } from '@tanstack/react-query'
import { api } from '@/shared/api/client'
import { checkedConnectionsQuery, connectionsQuery, type GoogleClient, googleClientQuery } from './queries'

/** The browser leaves for Google, which brings it back to the page of its answer (ADR 0120). */
export function useConnectGoogle(
  goTo: (address: string) => void = (address) => window.location.assign(address),
) {
  return api.useMutation('post', '/api/me/connections/google/authorizations', {
    onSuccess: ({ address }) => goTo(address),
  })
}

/** The code Google brought back, given to the API; the page of the answer says how it went. */
export function useGiveGoogleItsAnswer() {
  const queryClient = useQueryClient()

  return api.useMutation('post', '/api/me/connections/google', {
    meta: { silentClientErrors: true },
    onSuccess: (connections) => queryClient.setQueryData(connectionsQuery().queryKey, connections),
  })
}

export function useDisconnectGoogle() {
  const queryClient = useQueryClient()

  return api.useMutation('delete', '/api/me/connections/google', {
    onSuccess: (connections) => queryClient.setQueryData(connectionsQuery().queryKey, connections),
  })
}

/**
 * Another client of the account changes what is offered to its members, and makes those connected through a former
 * one reconnect (ADR 0125): their connections are read again.
 */
function useKeepTheGoogleClient() {
  const queryClient = useQueryClient()

  return (client: GoogleClient) => {
    queryClient.setQueryData(googleClientQuery().queryKey, client)
    void queryClient.invalidateQueries({ queryKey: connectionsQuery().queryKey })
    void queryClient.invalidateQueries({ queryKey: checkedConnectionsQuery().queryKey })
  }
}

export function useConfigureGoogleClient() {
  const keep = useKeepTheGoogleClient()

  return api.useMutation('put', '/api/me/organization/clients/google', { onSuccess: keep })
}

export function useRemoveGoogleClient() {
  const keep = useKeepTheGoogleClient()

  return api.useMutation('delete', '/api/me/organization/clients/google', { onSuccess: keep })
}
