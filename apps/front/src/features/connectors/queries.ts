import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

export type Connections = components['schemas']['ConnectionsResource']
export type Connection = components['schemas']['ConnectionResource']
export type GoogleClient = components['schemas']['GoogleClientResource']

/** My connections in the state last seen. The app's banner reads them without asking the providers. */
export const connectionsQuery = () => api.queryOptions('get', '/api/me/connections')

/**
 * My connections as their providers currently see them. The query runs each time the connectors page opens. It does
 * not retry on a failure, because a provider out of reach answers after its timeout. It is not cached once read,
 * because the connections keep the answer.
 */
export const checkedConnectionsQuery = () => ({
  ...api.queryOptions('post', '/api/me/connections/checks'),
  staleTime: 0,
  gcTime: 0,
  retry: false,
})

export const googleConnectionOf = (connections: Connections): Connection | undefined =>
  connections.connections.find((connection) => connection.provider === 'google')

/** The API offers to connect Google only while a Prospecter client at Google serves the account. */
export const isGoogleOffered = (connections: Connections) => connections._links.connectGoogle !== undefined

export const isAnyToReconnect = (connections: Connections) =>
  connections.connections.some((connection) => connection.state === 'toReconnect')

/** The account's Google client. Only an administrator reads it, and the connections link it to them (ADR 0125). */
export const googleClientQuery = () => api.queryOptions('get', '/api/me/organization/clients/google')

export const isTheGoogleClientConfigurable = (connections: Connections) =>
  connections._links.googleClient !== undefined
