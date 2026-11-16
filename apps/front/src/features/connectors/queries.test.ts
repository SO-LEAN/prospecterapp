import { describe, expect, it } from 'vitest'
import { type Connection, type Connections, googleConnectionOf, isAnyToReconnect } from './queries'

const google = (state: Connection['state']): Connection => ({
  provider: 'google',
  state,
  account: 'michel@exemple.fr',
  connectedAt: '2026-11-14T09:30:00+00:00',
  synced: 0,
  failed: 0,
  _links: {
    disconnect: { href: '/api/me/connections/google', method: 'DELETE' },
    reconnect: { href: '/api/me/connections/google/authorizations', method: 'POST' },
  },
})

const connections = (...items: Connection[]): Connections => ({
  connections: items,
  _links: {
    self: { href: '/api/me/connections', method: 'GET' },
    check: { href: '/api/me/connections/checks', method: 'POST' },
    connectGoogle: { href: '/api/me/connections/google/authorizations', method: 'POST' },
  },
})

describe('my connections', () => {
  it('finds my connection to Google, none before I connect', () => {
    expect(googleConnectionOf(connections(google('connected')))?.account).toBe('michel@exemple.fr')
    expect(googleConnectionOf(connections())).toBeUndefined()
  })

  it('tells a connection to reconnect, for the banner', () => {
    expect(isAnyToReconnect(connections(google('toReconnect')))).toBe(true)
    expect(isAnyToReconnect(connections(google('connected')))).toBe(false)
    expect(isAnyToReconnect(connections())).toBe(false)
  })
})
