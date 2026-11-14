import { useQueryClient } from '@tanstack/react-query'
import { api } from '@/shared/api/client'
import { connectionsQuery } from './queries'

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
