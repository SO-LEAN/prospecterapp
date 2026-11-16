import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { TooltipProvider } from '@/shared/ui/tooltip'
import type { GoogleSync as Sync } from '../queries'
import { GoogleSync } from './google-sync'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const EXCHANGE = '01900000-0000-7000-8000-0000000000e1'
const self = { href: `/api/exchanges/${EXCHANGE}/google-sync`, method: 'GET' }
const retry = { href: `/api/exchanges/${EXCHANGE}/google-sync/retries`, method: 'POST' }

const answered = (sync: Sync['sync'], links: Sync['_links'] = { self }): Sync => ({ sync, _links: links })

function renderTheSync() {
  render(
    <QueryClientProvider client={new QueryClient()}>
      <TooltipProvider>
        <GoogleSync exchangeId={EXCHANGE} />
      </TooltipProvider>
    </QueryClientProvider>,
  )

  return userEvent.setup()
}

const sent = (call: number) => {
  const request = theApi.mock.calls[call]?.[0] as Request

  return { path: new URL(request.url).pathname, method: request.method }
}

afterEach(() => theApi.mockReset())

describe('GoogleSync', () => {
  it('says the exchange is in my Google calendar, and since when', async () => {
    theApi.mockResolvedValue(
      Response.json(
        answered({ provider: 'google', state: 'synced', at: '2026-11-16T09:00:00+00:00', reason: null }),
      ),
    )
    renderTheSync()

    expect(await screen.findByLabelText(/^Synchronisé avec Google le 16 nov\. 2026/)).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Réessayer' })).not.toBeInTheDocument()
  })

  it('says the exchange awaits my connection to Google, to reconnect', async () => {
    theApi.mockResolvedValue(
      Response.json(answered({ provider: 'google', state: 'pending', at: null, reason: 'toReconnect' })),
    )
    renderTheSync()

    expect(
      await screen.findByLabelText(
        /^En attente de synchronisation avec Google.: votre compte Google est à reconnecter\.$/,
      ),
    ).toBeInTheDocument()
  })

  it('says Google refused the exchange, and pushes it again', async () => {
    theApi
      .mockResolvedValueOnce(
        Response.json(
          answered(
            {
              provider: 'google',
              state: 'failed',
              at: '2026-11-16T09:00:00+00:00',
              reason: 'calendarRefusedThePush',
            },
            { self, retry },
          ),
        ),
      )
      .mockResolvedValueOnce(
        Response.json(
          answered({ provider: 'google', state: 'synced', at: '2026-11-16T09:05:00+00:00', reason: null }),
        ),
      )
    const user = renderTheSync()

    expect(await screen.findByLabelText(/^Google a refusé l’événement le 16 nov\. 2026/)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Réessayer' }))

    expect(sent(1)).toEqual({ path: `/api/exchanges/${EXCHANGE}/google-sync/retries`, method: 'POST' })
    expect(await screen.findByLabelText(/^Synchronisé avec Google/)).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Réessayer' })).not.toBeInTheDocument()
  })

  it('shows nothing while the exchange is not pushed into my calendar', async () => {
    theApi.mockResolvedValue(Response.json(answered(null)))
    const { container } = render(
      <QueryClientProvider client={new QueryClient()}>
        <TooltipProvider>
          <GoogleSync exchangeId={EXCHANGE} />
        </TooltipProvider>
      </QueryClientProvider>,
    )

    await vi.waitFor(() => expect(theApi).toHaveBeenCalled())
    expect(container.querySelector('[role="img"]')).toBeNull()
  })
})
