import { useQueryClient } from '@tanstack/react-query'
import { api } from '@/shared/api/client'
import { checkedConnectionsQuery, connectionsQuery, type GoogleClient, googleClientQuery } from './queries'

/** The browser leaves for Google. Google brings it back to the answer page (ADR 0120). */
export function useConnectGoogle(
  goTo: (address: string) => void = (address) => window.location.assign(address),
) {
  return api.useMutation('post', '/api/me/connections/google/authorizations', {
    onSuccess: ({ address }) => goTo(address),
  })
}

/** The browser leaves for Google. Google asks for access to Gmail along with the calendar (ADR 0154). */
export function useImportGmail(
  goTo: (address: string) => void = (address) => window.location.assign(address),
) {
  return api.useMutation('post', '/api/me/connections/google/gmail-import/authorizations', {
    onSuccess: ({ address }) => goTo(address),
  })
}

export function useStopGmailImport() {
  const queryClient = useQueryClient()

  return api.useMutation('delete', '/api/me/connections/google/gmail-import', {
    onSuccess: (connections) => queryClient.setQueryData(connectionsQuery().queryKey, connections),
  })
}

/** Gives the API the code that Google brought back. The answer page says how it went. */
export function useGiveGoogleItsAnswer() {
  const queryClient = useQueryClient()

  return api.useMutation('post', '/api/me/connections/google', {
    meta: { silentClientErrors: true },
    onSuccess: (connections) => queryClient.setQueryData(connectionsQuery().queryKey, connections),
  })
}

/**
 * Pushes again each exchange that Google refused. The connections return the new synced and failed counts (ADR 0133).
 */
export function useRetryFailedPushes() {
  const queryClient = useQueryClient()

  return api.useMutation('post', '/api/me/connections/google/retries', {
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
 * A new client for the account changes what its members are offered. Members connected through the former client
 * must reconnect (ADR 0125). So their connections are read again.
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

  return api.useMutation('patch', '/api/me/organization/clients/google', { onSuccess: keep })
}

export function useRemoveGoogleClient() {
  const keep = useKeepTheGoogleClient()

  return api.useMutation('delete', '/api/me/organization/clients/google', { onSuccess: keep })
}

export function useConfigureGmailNotifications() {
  const keep = useKeepTheGoogleClient()

  return api.useMutation('patch', '/api/me/organization/clients/google/gmail-notifications', {
    onSuccess: keep,
  })
}

export function useRemoveGmailNotifications() {
  const keep = useKeepTheGoogleClient()

  return api.useMutation('delete', '/api/me/organization/clients/google/gmail-notifications', {
    onSuccess: keep,
  })
}
