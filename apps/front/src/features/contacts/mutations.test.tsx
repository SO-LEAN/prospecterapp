import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, renderHook } from '@testing-library/react'
import type { ReactNode } from 'react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { theDashboardInTheCacheOf } from '@/test/the-dashboard'
import { useForgetContact } from './mutations'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const GRACE = '0190a1c0-0000-7000-8000-000000000003'

function withTheCache(queryClient: QueryClient) {
  return ({ children }: { children: ReactNode }) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  )
}

afterEach(() => {
  vi.useRealTimers()
  theApi.mockReset()
})

describe('the gestures on a contact', () => {
  it('have the dashboard read again once the reactions to a forgotten contact counted it', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    theApi.mockResolvedValue(new Response(null, { status: 204 }))
    const queryClient = new QueryClient()
    const dashboard = theDashboardInTheCacheOf(queryClient)
    const { result } = renderHook(useForgetContact, { wrapper: withTheCache(queryClient) })

    await act(() => result.current.mutateAsync({ params: { path: { id: GRACE } } }))

    expect(await dashboard.isReadAgainAfterTheReactions()).toBe(true)
  })
})
