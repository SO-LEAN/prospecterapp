import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Toaster, toast } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { type Connection, type Connections, connectionsQuery } from '../queries'
import { GoogleCalendarCard } from './google-calendar-card'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const connection = (state: Connection['state']): Connection => ({
  provider: 'google',
  state,
  account: 'michel@exemple.fr',
  connectedAt: '2026-11-14T09:30:00+00:00',
  _links: {
    disconnect: { href: '/api/me/connections/google', method: 'DELETE' },
    reconnect: { href: '/api/me/connections/google/authorizations', method: 'POST' },
  },
})

const noConnection: Connections = {
  connections: [],
  _links: {
    self: { href: '/api/me/connections', method: 'GET' },
    check: { href: '/api/me/connections/checks', method: 'POST' },
    connectGoogle: { href: '/api/me/connections/google/authorizations', method: 'POST' },
  },
}

function renderTheCard(shown: Connection | undefined) {
  const queryClient = new QueryClient()
  const goTo = vi.fn()
  render(
    <QueryClientProvider client={queryClient}>
      <GoogleCalendarCard connection={shown} goTo={goTo} />
      <Toaster />
    </QueryClientProvider>,
  )

  return { user: userEvent.setup(), queryClient, goTo }
}

const sent = () => {
  const request = theApi.mock.calls[0]?.[0] as Request

  return { path: new URL(request.url).pathname, method: request.method }
}

afterEach(() => {
  theApi.mockReset()
  toast.dismiss()
})

describe('GoogleCalendarCard', () => {
  it('connects my account by sending me to the address of Google', async () => {
    theApi.mockResolvedValue(
      Response.json({ address: 'https://accounts.google.test/auth?state=s' }, { status: 201 }),
    )
    const { user, goTo } = renderTheCard(undefined)

    await user.click(screen.getByRole('button', { name: 'Connecter' }))

    expect(sent()).toEqual({ path: '/api/me/connections/google/authorizations', method: 'POST' })
    await waitFor(() => expect(goTo).toHaveBeenCalledWith('https://accounts.google.test/auth?state=s'))
  })

  it('shows the account connected and since when, to reconnect or disconnect', () => {
    renderTheCard(connection('connected'))

    expect(screen.getByText(/Connecté au compte michel@solean-it\.com depuis le/)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Reconnecter' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Déconnecter' })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Connecter' })).not.toBeInTheDocument()
  })

  it('says a connection Google refuses is to reconnect', () => {
    renderTheCard(connection('toReconnect'))

    expect(
      screen.getByText(/Google n’accorde plus l’accès au compte michel@solean-it\.com/),
    ).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Reconnecter' })).toBeInTheDocument()
  })

  it('disconnects my account once confirmed, and keeps the connections the API answers', async () => {
    theApi.mockResolvedValue(Response.json(noConnection))
    const { user, queryClient } = renderTheCard(connection('connected'))

    await user.click(screen.getByRole('button', { name: 'Déconnecter' }))
    expect(theApi).not.toHaveBeenCalled()
    await user.click(within(await screen.findByRole('dialog')).getByRole('button', { name: 'Déconnecter' }))

    await waitFor(() => expect(sent()).toEqual({ path: '/api/me/connections/google', method: 'DELETE' }))
    await waitFor(() => expect(queryClient.getQueryData(connectionsQuery().queryKey)).toEqual(noConnection))
    expect(await screen.findByText('Votre compte Google est déconnecté.')).toBeInTheDocument()
  })
})
