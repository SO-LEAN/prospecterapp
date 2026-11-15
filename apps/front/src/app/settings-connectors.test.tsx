import { screen, waitFor } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Connections } from '@/features/connectors/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/**
 * The section of the connectors, on the routes of the SPA: they are checked with their providers as it opens, never
 * as a link to it is only hovered (the router preloads it then), a check costing a call to Google.
 */

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: {
    accessToken: async () => 'an access token',
    renewedAccessToken: async () => null,
    signOut: async () => {},
  },
}))

const CHECKS = '/api/me/connections/checks'

const me: Account = {
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

const noConnection: Connections = {
  connections: [],
  _links: {
    self: { href: '/api/me/connections', method: 'GET' },
    check: { href: CHECKS, method: 'POST' },
    connectGoogle: { href: '/api/me/connections/google/authorizations', method: 'POST' },
  },
}

const myReminders = {
  enabled: false,
  minutesBefore: 15,
  _links: {
    self: { href: '/api/me/reminders', method: 'GET' },
    changeMyReminders: { href: '/api/me/reminders', method: 'PATCH' },
  },
}

const googleNotOffered: Connections = {
  connections: [],
  _links: { self: noConnection._links.self, check: noConnection._links.check },
}

function theApiAnswers(connections: Connections = noConnection) {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL(input instanceof Request ? input.url : String(input))
    const answers: Record<string, unknown> = {
      '/api/me': me,
      '/api/me/reminders': myReminders,
      '/api/me/connections': connections,
      [CHECKS]: connections,
    }
    return Response.json(answers[pathname] ?? { items: [], _links: {} })
  })
}

const checksSent = () =>
  theApi.mock.calls.filter(([input]) => input instanceof Request && new URL(input.url).pathname === CHECKS)
    .length

describe('the section of the connectors', () => {
  afterEach(() => {
    theApi.mockReset()
  })

  it('checks the connections as it opens, not as a link to it is hovered', async () => {
    theApiAnswers()
    const router = await renderTheSpaAt('/settings')

    await router.preloadRoute({ to: '/settings', search: { section: 'connectors' } })
    expect(checksSent()).toBe(0)

    await router.navigate({ to: '/settings', search: { section: 'connectors' } })

    expect(await screen.findByText('Google Agenda')).toBeInTheDocument()
    await waitFor(() => expect(checksSent()).toBe(1))
  })

  it('says Google is unavailable, without offering to connect it, while the API does not offer it', async () => {
    theApiAnswers(googleNotOffered)
    const router = await renderTheSpaAt('/settings')

    await router.navigate({ to: '/settings', search: { section: 'connectors' } })

    expect(await screen.findByText('Google Agenda n’est pas disponible pour le moment.')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Connecter' })).not.toBeInTheDocument()
  })
})
