import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderTheSpaAt } from '@/test/render-the-spa'

/**
 * The dashboard on the SPA's routes (`routes/_app/index.tsx`). The organizations' map has no page. The located
 * organizations' figure leads to it, on the dashboard.
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
    signOutEverywhere: async () => {},
  },
}))

const NO_BREAK_SPACE = '\u00a0'

const json = (body: unknown, status = 200) =>
  new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })

const notFound = () =>
  json(
    { type: 'about:blank', title: 'Refused', status: 404, detail: 'Refused: notFound', reason: 'notFound' },
    404,
  )

const self = (href: string) => ({ self: { href, method: 'GET' } })

const myAnswers: Record<string, unknown> = {
  '/api/me/sign-in': { loginEmail: 'ada@babbage.test' },
  '/api/me': {
    name: { firstName: 'Ada', lastName: 'Lovelace' },
    contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
    organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
    _links: self('/api/me'),
  },
  '/api/dashboard': {
    organizationName: 'Babbage & Co',
    markers: [],
    stats: {
      activeOrganizations: 0,
      locatedOrganizations: 3,
      contacts: 0,
      exchanges: 0,
      exchangesThisMonth: 0,
    },
  },
  '/api/organizations': { items: [], _links: {} },
  '/api/me/day': { today: [], next: [], _links: self('/api/me/day') },
  '/api/me/exchanges-to-process': {
    items: [],
    toDos: [],
    contactsToAttach: [],
    _links: self('/api/me/exchanges-to-process'),
  },
}

/** The API serves my account, my dashboard, an empty day and nothing to process. It serves nothing else. */
function theApiAnswersMyDashboard() {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL(input instanceof Request ? input.url : String(input))
    return pathname in myAnswers ? json(myAnswers[pathname]) : notFound()
  })
}

describe('the map of the organizations on the dashboard', () => {
  it('is where the figure of the located organizations leads', async () => {
    theApiAnswersMyDashboard()
    const router = await renderTheSpaAt('/')

    await userEvent.click(
      await screen.findByRole('link', {
        name: `3 Organisations géolocalisées${NO_BREAK_SPACE}: voir la carte des organisations`,
      }),
    )

    expect(router.state.location.pathname).toBe('/')
    expect(document.getElementById(router.state.location.hash)).toHaveTextContent('Carte des organisations')
  })
})
