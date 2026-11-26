import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Contact } from '@/features/contacts/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** A new contact from the contacts list, on the SPA's routes: its organization is chosen in the same form. */

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

const ACME = '0190a1c0-0000-7000-8000-00000000a000'
const GRACE = '0190a1c0-0000-7000-8000-000000000002'

const me: Account = {
  name: { firstName: 'Alan', lastName: 'Turing' },
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

const grace: Contact = {
  id: GRACE,
  revision: 1,
  name: { firstName: 'Grace', lastName: 'Hopper' },
  possibleDuplicates: [],
  createdByAnAI: false,
  title: 'x',
  jobTitle: 'Directrice technique',
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  masked: false,
  _links: { self: { href: `/api/contacts/${GRACE}`, method: 'GET' } },
}

const COLLECT = { collect: { href: '/api/contacts', method: 'POST' } }

/** The API serves my account, the contacts list with its links, the prospected organizations and Grace once collected. */
function theApiAnswers(listLinks: object) {
  const sent: { method: string; path: string; body: unknown }[] = []
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const url = new URL(request.url)
    if (request.method !== 'GET') {
      sent.push({ method: request.method, path: url.pathname, body: await request.json() })
      return Response.json(grace, { status: 201, headers: { Location: `/api/contacts/${GRACE}` } })
    }
    const answers: Record<string, unknown> = {
      '/api/me': me,
      '/api/me/connections': {
        connections: [],
        _links: { self: { href: '/api/me/connections', method: 'GET' } },
      },
      '/api/me/reminders': {
        enabled: false,
        minutesBefore: 10,
        _links: { self: { href: '/api/me/reminders', method: 'GET' } },
      },
      '/api/me/day': { today: [], next: [], _links: { self: { href: '/api/me/day', method: 'GET' } } },
      '/api/me/exchanges-to-process': { items: [], toDos: [], contactsToAttach: [], _links: {} },
      '/api/contacts': {
        items: [],
        _links: {
          self: { href: '/api/contacts', method: 'GET' },
          search: { href: '/api/contacts', method: 'GET' },
          ...listLinks,
        },
      },
      '/api/organizations': {
        items: [{ id: ACME, name: 'ACME', possibleDuplicates: [] }],
        _links: { self: { href: '/api/organizations', method: 'GET' } },
      },
      [`/api/contacts/${GRACE}`]: grace,
      [`/api/contacts/${GRACE}/organizations`]: {
        items: [
          { id: ACME, name: 'ACME', _links: { self: { href: `/api/organizations/${ACME}`, method: 'GET' } } },
        ],
        _links: { self: { href: `/api/contacts/${GRACE}/organizations`, method: 'GET' } },
      },
    }
    return Response.json(answers[url.pathname] ?? { items: [], _links: {} })
  })
  return sent
}

describe('a new contact from the contacts list', () => {
  afterEach(() => theApi.mockReset())

  it('collects the contact for the organization chosen, in one request, then opens their card', async () => {
    const sent = theApiAnswers(COLLECT)
    const router = await renderTheSpaAt('/contacts')

    await userEvent.click(await screen.findByRole('link', { name: 'Nouveau contact' }))
    await userEvent.type(await screen.findByRole('searchbox', { name: 'Organisation' }), 'AC')
    await userEvent.click(await screen.findByRole('button', { name: 'ACME' }))
    await userEvent.type(screen.getByLabelText('Prénom'), 'Grace')
    await userEvent.type(screen.getByLabelText('Nom'), 'Hopper')
    await userEvent.type(screen.getByLabelText(/Fonction/), 'Directrice technique')
    await userEvent.click(screen.getByRole('button', { name: 'Enregistrer le contact' }))

    expect(await screen.findByRole('heading', { name: 'Grace Hopper' })).toBeInTheDocument()
    expect(router.state.location.pathname).toBe(`/contacts/${GRACE}`)
    expect(sent).toEqual([
      {
        method: 'POST',
        path: '/api/contacts',
        body: {
          name: { firstName: 'Grace', lastName: 'Hopper' },
          title: 'x',
          jobTitle: 'Directrice technique',
          contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
          organizationIds: [ACME],
        },
      },
    ])
  })

  it('offers no new contact to a member the list does not offer to collect one', async () => {
    theApiAnswers({})
    await renderTheSpaAt('/contacts')

    expect(await screen.findByRole('heading', { name: 'Contacts' })).toBeInTheDocument()
    expect(screen.getByRole('searchbox')).toBeInTheDocument()
    expect(screen.queryByRole('link', { name: 'Nouveau contact' })).not.toBeInTheDocument()
  })
})
