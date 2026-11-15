import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

export type Connections = components['schemas']['ConnectionsResource']
export type Connection = components['schemas']['ConnectionResource']

/** My connections in the state last seen, read by the banner of the app without asking the providers. */
export const connectionsQuery = () => api.queryOptions('get', '/api/me/connections')

/** My connections as their providers see them now: asked each time the page of the connectors opens. */
export const checkedConnectionsQuery = () => ({
  ...api.queryOptions('post', '/api/me/connections/checks'),
  staleTime: 0,
})

export const googleConnectionOf = (connections: Connections): Connection | undefined =>
  connections.connections.find((connection) => connection.provider === 'google')

/** The API offers to connect Google only while a client of Prospecter at Google serves the account. */
export const isGoogleOffered = (connections: Connections) => connections._links.connectGoogle !== undefined

export const isAnyToReconnect = (connections: Connections) =>
  connections.connections.some((connection) => connection.state === 'toReconnect')
