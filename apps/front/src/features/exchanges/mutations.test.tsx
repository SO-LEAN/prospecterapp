import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, renderHook } from '@testing-library/react'
import type { ReactNode } from 'react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { theDashboardInTheCacheOf } from '@/test/the-dashboard'
import { useEraseExchange, useNoteExchange } from './mutations'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const GRACE = '0190a1c0-0000-7000-8000-000000000003'
const A_CALL = '0190a1c0-0000-7000-8000-000000000004'

function withTheCache(queryClient: QueryClient) {
  return ({ children }: { children: ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  )
}

afterEach(() => {
  vi.useRealTimers()
  theApi.mockReset()
})

describe('the gestures on the exchanges', () => {
  it('have the dashboard read again once the reactions to a noted exchange counted it', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    theApi.mockResolvedValue(
      Response.json(
        { items: [], _links: { self: { href: '/', method: 'GET' } } },
        { status: 201, headers: { Location: `/api/exchanges/${A_CALL}` } },
      ),
    )
    const queryClient = new QueryClient()
    const dashboard = theDashboardInTheCacheOf(queryClient)
    const { result } = renderHook(useNoteExchange, { wrapper: withTheCache(queryClient) })

    await act(() =>
      result.current.mutateAsync({
        contactId: GRACE,
        body: {
          channel: 'call',
          direction: 'inbound',
          occurredAt: '2026-11-11T09:00:00+00:00',
          text: null,
          missed: false,
        },
      }),
    )

    expect(await dashboard.isReadAgainAfterTheReactions()).toBe(true)
  })

  it('have the dashboard read again once the reactions to an erased exchange counted it', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    theApi.mockResolvedValue(new Response(null, { status: 204 }))
    const queryClient = new QueryClient()
    const dashboard = theDashboardInTheCacheOf(queryClient)
    const { result } = renderHook(useEraseExchange, { wrapper: withTheCache(queryClient) })

    await act(() => result.current.mutateAsync({ params: { path: { id: A_CALL } } }))

    expect(await dashboard.isReadAgainAfterTheReactions()).toBe(true)
  })
})
