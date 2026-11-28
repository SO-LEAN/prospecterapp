import { MutationCache, QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, render, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { queryClient as theAppQueryClient } from '@/app/query-client'
import { Toaster } from '@/shared/ui/sonner'
import { UNDO_DELAY_MS } from '../mutations'
import type { Exchange } from '../queries'
import { ExchangeGestures } from './exchange-gestures'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

/** The toaster follows the system's theme, which jsdom does not provide. */
vi.hoisted(() => {
  window.matchMedia = (query: string) =>
    ({
      matches: false,
      media: query,
      addEventListener: () => undefined,
      removeEventListener: () => undefined,
    }) as unknown as MediaQueryList
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const EXCHANGE = '0190a1c0-0000-7000-8000-000000000010'

const LINKS = {
  self: { href: `/api/exchanges/${EXCHANGE}`, method: 'GET' },
  move: { href: `/api/exchanges/${EXCHANGE}/moment`, method: 'PATCH' },
  markDone: { href: `/api/exchanges/${EXCHANGE}/done`, method: 'POST' },
  markMissed: { href: `/api/exchanges/${EXCHANGE}/missed`, method: 'POST' },
  cancel: { href: `/api/exchanges/${EXCHANGE}/cancellation`, method: 'POST' },
} satisfies Exchange['_links']

function showTheGestures(links: Exchange['_links'], onOpen = vi.fn(), onLeave?: () => () => void) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
    mutationCache: new MutationCache(theAppQueryClient.getMutationCache().config),
  })
  render(
    <QueryClientProvider client={queryClient}>
      <ExchangeGestures exchange={{ id: EXCHANGE, _links: links }} onOpen={onOpen} onLeave={onLeave} />
      <Toaster />
    </QueryClientProvider>,
  )
  return onOpen
}

const theGestures = () => screen.queryAllByRole('button').map((button) => button.textContent?.trim())

/** Each command the API received, by its method and its path. */
const theCommandsSent = () =>
  theApi.mock.calls
    .map(([request]) => request as Request)
    .filter((request) => request.method !== 'GET')
    .map((request) => `${request.method} ${new URL(request.url).pathname}`)

/** The body of the last command the API received. */
const theBodySent = () =>
  (
    theApi.mock.calls
      .map(([request]) => request as Request)
      .findLast((request) => request.method !== 'GET') as Request
  ).json()

/** Lets time run so that the toasts close. The user waits for them. */
function letTimeRun() {
  vi.useFakeTimers({ shouldAdvanceTime: true })
  return userEvent.setup({ advanceTimers: (delay) => vi.advanceTimersByTime(delay) })
}

beforeEach(() => {
  theApi.mockImplementation(async () => Response.json({}))
})

afterEach(() => {
  vi.useRealTimers()
  theApi.mockReset()
})

describe('the gestures of an exchange’s line', () => {
  it('offers marking it done, missed, cancelled and moving it when its links offer them', () => {
    showTheGestures(LINKS)

    expect(theGestures()).toEqual(['Fait', 'Manqué', 'Annulé', 'Déplacer'])
  })

  it('offers only the gestures its links offer', () => {
    showTheGestures({ self: LINKS.self, move: LINKS.move })

    expect(theGestures()).toEqual(['Déplacer'])
  })

  it('opens its detail on its report to mark it done', async () => {
    const onOpen = showTheGestures(LINKS)

    await userEvent.click(screen.getByRole('button', { name: 'Fait' }))

    expect(onOpen).toHaveBeenCalledExactlyOnceWith('report')
  })

  it('opens its detail on its move to move it', async () => {
    const onOpen = showTheGestures(LINKS)

    await userEvent.click(screen.getByRole('button', { name: 'Déplacer' }))

    expect(onOpen).toHaveBeenCalledExactlyOnceWith('move')
  })

  it('marks it missed by one command, once its toast no longer takes it back, the line taken away meanwhile', async () => {
    const user = letTimeRun()
    const onLeave = vi.fn(() => () => undefined)
    showTheGestures(LINKS, vi.fn(), onLeave)

    await user.click(screen.getByRole('button', { name: 'Manqué' }))

    expect(await screen.findByText('Échange marqué manqué')).toBeInTheDocument()
    expect(onLeave).toHaveBeenCalledOnce()
    expect(theCommandsSent()).toEqual([])
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))
    await waitFor(() => expect(theCommandsSent()).toEqual([`POST /api/exchanges/${EXCHANGE}/missed`]))
  })

  it('cancels it by one command without saying who called it off, once its toast no longer takes it back', async () => {
    const user = letTimeRun()
    const onLeave = vi.fn(() => () => undefined)
    showTheGestures(LINKS, vi.fn(), onLeave)

    await user.click(screen.getByRole('button', { name: 'Annulé' }))

    expect(await screen.findByText('Échange annulé')).toBeInTheDocument()
    expect(onLeave).toHaveBeenCalledOnce()
    expect(theCommandsSent()).toEqual([])
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))
    await waitFor(() => expect(theCommandsSent()).toEqual([`POST /api/exchanges/${EXCHANGE}/cancellation`]))
    expect(await theBodySent()).toEqual({ canceller: null })
  })

  it('puts the line back when its toast takes the gesture back', async () => {
    const user = letTimeRun()
    const back = vi.fn()
    showTheGestures(LINKS, vi.fn(), () => back)

    await user.click(screen.getByRole('button', { name: 'Manqué' }))
    const toasts = await screen.findAllByText('Échange marqué manqué')
    const toast = toasts[0]?.closest('[data-sonner-toast]') as HTMLElement
    await user.click(within(toast).getByRole('button', { name: 'Annuler' }))
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))

    expect(back).toHaveBeenCalledOnce()
    expect(theCommandsSent()).toEqual([])
  })
})
