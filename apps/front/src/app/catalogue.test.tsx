import { screen } from '@testing-library/react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** The events' catalogue on the SPA's routes, for a signed-in member. */

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

const me: Account = {
  name: { firstName: 'Ada', lastName: 'Lovelace' },
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

/** The API serves my account, and nothing else. */
function theApiServesMyAccount() {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL(input instanceof Request ? input.url : String(input))
    const self = { self: { href: pathname, method: 'GET' } }

    return Response.json(pathname === '/api/me' ? me : { connections: [], items: [], _links: self })
  })
}

describe('the events catalogue', () => {
  beforeEach(theApiServesMyAccount)

  afterEach(() => {
    theApi.mockReset()
  })

  it('opens at /catalogue', async () => {
    await renderTheSpaAt('/catalogue')

    expect(
      await screen.findByRole('heading', { name: 'Catalogue des événements', level: 1 }),
    ).toBeInTheDocument()
    expect(screen.getByRole('table', { name: 'Canal et statut' })).toBeInTheDocument()
  })
})
