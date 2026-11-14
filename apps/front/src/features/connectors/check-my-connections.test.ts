import { QueryClient } from '@tanstack/react-query'
import { describe, expect, it, vi } from 'vitest'
import { checkMyConnections } from './check-my-connections'
import { type Connections, connectionsQuery } from './queries'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const checked: Connections = {
  connections: [],
  _links: {
    self: { href: '/api/me/connections', method: 'GET' },
    check: { href: '/api/me/connections/checks', method: 'POST' },
    connectGoogle: { href: '/api/me/connections/google/authorizations', method: 'POST' },
  },
}

const sent = () => {
  const request = theApi.mock.calls.at(-1)?.[0] as Request

  return { path: new URL(request.url).pathname, method: request.method }
}

describe('checkMyConnections', () => {
  it('asks the API to check my connections each time, and keeps its answer as their state', async () => {
    theApi.mockImplementation(async () => Response.json(checked))
    const queryClient = new QueryClient()

    await checkMyConnections(queryClient)
    await checkMyConnections(queryClient)

    expect(theApi).toHaveBeenCalledTimes(2)
    expect(sent()).toEqual({ path: '/api/me/connections/checks', method: 'POST' })
    expect(queryClient.getQueryData(connectionsQuery().queryKey)).toEqual(checked)
  })
})
