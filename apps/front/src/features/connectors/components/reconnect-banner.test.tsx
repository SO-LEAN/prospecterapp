import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import { type Connection, type Connections, connectionsQuery } from '../queries'
import { ReconnectBanner } from './reconnect-banner'

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const BANNER = 'Google n’accorde plus l’accès à votre agenda : vos échanges planifiés n’y sont plus ajoutés.'

const connections = (...states: Connection['state'][]): Connections => ({
  connections: states.map((state) => ({
    provider: 'google',
    state,
    account: 'michel@exemple.fr',
    connectedAt: '2026-11-14T09:30:00+00:00',
    synced: 0,
    failed: 0,
    gmailImport: 'off',
    lastGmailNotificationAt: null,
    _links: {
      disconnect: { href: '/api/me/connections/google', method: 'DELETE' },
      reconnect: { href: '/api/me/connections/google/authorizations', method: 'POST' },
    },
  })),
  _links: {
    self: { href: '/api/me/connections', method: 'GET' },
    check: { href: '/api/me/connections/checks', method: 'POST' },
    connectGoogle: { href: '/api/me/connections/google/authorizations', method: 'POST' },
  },
})

async function renderTheBanner(known: Connections) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(connectionsQuery().queryKey, known)
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <ReconnectBanner />
    </QueryClientProvider>,
  )
}

describe('ReconnectBanner', () => {
  it('says above the page that Google no longer grants the access, linking to the connectors', async () => {
    await renderTheBanner(connections('toReconnect'))

    expect(screen.getByRole('status')).toHaveTextContent(BANNER)
    expect(screen.getByRole('link', { name: 'Reconnecter' })).toHaveAttribute(
      'href',
      '/settings?section=connectors',
    )
  })

  it('says nothing while my connections are connected, or when I have none', async () => {
    await renderTheBanner(connections('connected'))
    expect(screen.queryByRole('status')).not.toBeInTheDocument()
  })
})
