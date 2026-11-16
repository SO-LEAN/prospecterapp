import { screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
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
const AUTHORIZATIONS = '/api/me/connections/google/authorizations'

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
    connectGoogle: { href: AUTHORIZATIONS, method: 'POST' },
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
      '/api/me/exchanges-to-process': { items: [], toDos: [], _links: {} },
    }
    return Response.json(answers[pathname] ?? { items: [], _links: {} })
  })
}

/** The client of Google was removed since the section was shown: the API refuses the authorization. */
function theApiNoLongerOffersGoogle() {
  const answerAsBefore = theApi.getMockImplementation()
  theApi.mockImplementation(async (input, init) => {
    const { pathname } = new URL(input instanceof Request ? input.url : String(input))
    if (pathname !== AUTHORIZATIONS) {
      return answerAsBefore?.(input, init) ?? Response.error()
    }
    return Response.json(
      {
        type: 'about:blank',
        title: 'Conflict',
        status: 409,
        reason: 'providerUnavailable',
        detail: 'Google is not available',
      },
      { status: 409, headers: { 'Content-Type': 'application/problem+json' } },
    )
  })
}

const DISCONNECT = '/api/me/connections/google'

const connectedToGoogle: Connections = {
  connections: [
    {
      provider: 'google',
      state: 'connected',
      account: 'agenda@exemple.fr',
      connectedAt: '2026-11-02T09:30:00+00:00',
      synced: 0,
      failed: 0,
      _links: { disconnect: { href: DISCONNECT, method: 'DELETE' } },
    },
  ],
  _links: noConnection._links,
}

/** Google does not answer: the API cannot check the connections, it still tells them as last seen and forgets one. */
function googleDoesNotAnswer() {
  const answerAsBefore = theApi.getMockImplementation()
  theApi.mockImplementation(async (input, init) => {
    const request = input instanceof Request ? input : new Request(String(input), init)
    const { pathname } = new URL(request.url)
    if (pathname === DISCONNECT && request.method === 'DELETE') {
      return Response.json(noConnection)
    }
    if (pathname !== CHECKS) {
      return answerAsBefore?.(input, init) ?? Response.error()
    }
    return Response.json(
      {
        type: 'about:blank',
        title: 'Service Unavailable',
        status: 503,
        reason: 'googleUnreachable',
        detail: 'Google does not answer for now',
      },
      { status: 503, headers: { 'Content-Type': 'application/problem+json' } },
    )
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

  it('says in French that Google is no longer available when the API refuses to connect it', async () => {
    theApiAnswers()
    const router = await renderTheSpaAt('/settings')
    await router.navigate({ to: '/settings', search: { section: 'connectors' } })
    theApiNoLongerOffersGoogle()

    await userEvent.setup().click(await screen.findByRole('button', { name: 'Connecter' }))

    expect(await screen.findByText('Google n’est pas disponible pour le moment.')).toBeInTheDocument()
  })

  it('shows the connection as last seen and says Google does not answer while the check fails', async () => {
    theApiAnswers(connectedToGoogle)
    googleDoesNotAnswer()
    const router = await renderTheSpaAt('/settings')

    await router.navigate({ to: '/settings', search: { section: 'connectors' } })

    expect(await screen.findByText(/Connecté au compte agenda@exemple\.fr/)).toBeInTheDocument()
    expect(
      screen.getByText(/^Google ne répond pas.: l’état de votre accès n’a pas pu être vérifié\.$/),
    ).toBeInTheDocument()
    expect(checksSent()).toBe(1)
  })

  it('disconnects the member while Google does not answer', async () => {
    theApiAnswers(connectedToGoogle)
    googleDoesNotAnswer()
    const router = await renderTheSpaAt('/settings')
    await router.navigate({ to: '/settings', search: { section: 'connectors' } })
    const user = userEvent.setup()

    await user.click(await screen.findByRole('button', { name: 'Déconnecter' }))
    await user.click(within(await screen.findByRole('dialog')).getByRole('button', { name: 'Déconnecter' }))

    expect(await screen.findByRole('button', { name: 'Connecter' })).toBeInTheDocument()
    expect(screen.queryByText(/Connecté au compte agenda@exemple\.fr/)).not.toBeInTheDocument()
  })
})
