import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, renderHook } from '@testing-library/react'
import type { ReactNode } from 'react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { theDashboardInTheCacheOf } from '@/test/the-dashboard'
import { parseDay, periodOf } from './calendar/dates'
import { calendarQuery } from './calendar/queries'
import {
  useCancelExchange,
  useEraseExchange,
  useMarkExchangeDone,
  useMarkExchangeMissed,
  useMoveExchange,
  useNoteExchange,
  usePlanExchange,
  usePlanOrganizationExchange,
} from './mutations'
import { type ExchangeDetail, exchangeQuery, timelineQuery } from './queries'

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
const ACME = '0190a1c0-0000-7000-8000-000000000005'
const A_WEEK_OF_MY_CALENDAR = calendarQuery(periodOf('week', parseDay('2026-11-16'))).queryKey

/** A period of my calendar in the cache, which a gesture on an exchange must have read again. */
function myCalendarInTheCacheOf(queryClient: QueryClient) {
  queryClient.setQueryData(A_WEEK_OF_MY_CALENDAR, { items: [] })
  return { isReadAgain: () => queryClient.getQueryState(A_WEEK_OF_MY_CALENDAR)?.isInvalidated }
}

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
    const calendar = myCalendarInTheCacheOf(queryClient)
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
    expect(calendar.isReadAgain()).toBe(true)
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

  it.each([
    ['marked done', useMarkExchangeDone, { occurredAt: '2026-11-10T13:00:00+00:00', text: null }, true],
    ['marked missed', useMarkExchangeMissed, undefined, true],
    ['cancelled', useCancelExchange, { canceller: null }, true],
    ['moved', useMoveExchange, { occurredAt: '2099-11-18T08:00:00+00:00' }, false],
  ] as const)(
    'show a planned exchange %s in its detail and read its timelines again',
    async (_gesture, useGesture, body, counted) => {
      vi.useFakeTimers({ shouldAdvanceTime: true })
      const handled = { id: A_CALL, status: 'done' } as ExchangeDetail
      theApi.mockResolvedValue(Response.json(handled))
      const queryClient = new QueryClient()
      const dashboard = theDashboardInTheCacheOf(queryClient)
      const calendar = myCalendarInTheCacheOf(queryClient)
      queryClient.setQueryData(timelineQuery(GRACE).queryKey, { pages: [], pageParams: [] })
      const { result } = renderHook(useGesture as typeof useMarkExchangeDone, {
        wrapper: withTheCache(queryClient),
      })

      await act(() =>
        result.current.mutateAsync({ params: { path: { id: A_CALL } }, body } as Parameters<
          typeof result.current.mutateAsync
        >[0]),
      )

      expect(queryClient.getQueryData(exchangeQuery(A_CALL).queryKey)).toEqual(handled)
      expect(queryClient.getQueryState(timelineQuery(GRACE).queryKey)?.isInvalidated).toBe(true)
      expect(await dashboard.isReadAgainAfterTheReactions()).toBe(counted)
      expect(calendar.isReadAgain()).toBe(true)
    },
  )

  it.each([
    [
      'with a contact',
      usePlanExchange,
      { params: { path: { id: GRACE } }, body: { channel: 'call', occurredAt: '2099-11-18T08:00:00+00:00' } },
    ],
    [
      'from the card of an organization',
      usePlanOrganizationExchange,
      {
        params: { path: { id: ACME } },
        body: { exchange: { channel: 'call', occurredAt: '2099-11-18T08:00:00+00:00' } },
      },
    ],
  ] as const)(
    'have my calendar read again once an exchange is planned %s',
    async (_from, usePlan, planned) => {
      theApi.mockResolvedValue(
        Response.json({ items: [], _links: { self: { href: '/', method: 'GET' } } }, { status: 201 }),
      )
      const queryClient = new QueryClient()
      const calendar = myCalendarInTheCacheOf(queryClient)
      const { result } = renderHook(usePlan as typeof usePlanExchange, { wrapper: withTheCache(queryClient) })

      await act(() => result.current.mutateAsync(planned as Parameters<typeof result.current.mutateAsync>[0]))

      expect(calendar.isReadAgain()).toBe(true)
    },
  )
})
