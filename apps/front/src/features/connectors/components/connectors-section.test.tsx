import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import { Suspense } from 'react'
import { describe, expect, it, vi } from 'vitest'
import { type Connections, connectionsQuery, type GoogleClient, googleClientQuery } from '../queries'
import { ConnectorsSection } from './connectors-section'

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const THE_CLIENT = '/api/me/organization/clients/google'

const connections = (forAnAdministrator: boolean): Connections => ({
  connections: [],
  _links: {
    self: { href: '/api/me/connections', method: 'GET' },
    check: { href: '/api/me/connections/checks', method: 'POST' },
    connectGoogle: { href: '/api/me/connections/google/authorizations', method: 'POST' },
    ...(forAnAdministrator ? { googleClient: { href: THE_CLIENT, method: 'GET' } } : {}),
  },
})

const theClient: GoogleClient = {
  clientId: 'client-of-solean',
  secretSet: true,
  redirectUri: 'https://local.prospecter.io/settings/connectors/google',
  _links: { self: { href: THE_CLIENT, method: 'GET' }, configure: { href: THE_CLIENT, method: 'PATCH' } },
}

function renderTheSection(forAnAdministrator: boolean) {
  const queryClient = new QueryClient()
  queryClient.setQueryData(connectionsQuery().queryKey, connections(forAnAdministrator))
  queryClient.setQueryData(googleClientQuery().queryKey, theClient)
  render(
    <QueryClientProvider client={queryClient}>
      <Suspense>
        <ConnectorsSection />
      </Suspense>
    </QueryClientProvider>,
  )
}

describe('ConnectorsSection', () => {
  it('lets an administrator configure the client of Google of the account', async () => {
    renderTheSection(true)

    expect(await screen.findByText('Client Google du compte')).toBeInTheDocument()
    expect(screen.getByText('Google Agenda')).toBeInTheDocument()
  })

  it('shows another member neither the client nor its configuration', async () => {
    renderTheSection(false)

    expect(await screen.findByText('Google Agenda')).toBeInTheDocument()
    expect(screen.queryByText('Client Google du compte')).not.toBeInTheDocument()
  })
})
