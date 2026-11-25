import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Organization } from '@/features/organizations/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** Creating a subsidiary on the SPA's routes, its holding chosen in the form. */

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

const GROUPE_ACME = '0190a1c0-0000-7000-8000-000000000001'
const ACME_EST = '0190a1c0-0000-7000-8000-000000000002'
const THE_PAGE_OF_THE_ORGANIZATION_LOADED = { timeout: 3_000 }

const me: Account = {
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

const anOrganization = (id: string, name: string, holdingId: string | null): Organization => ({
  id,
  revision: 1,
  name,
  legalIdentity: { corporateName: name },
  contactDetails: { links: [] },
  contactIds: [],
  possibleDuplicates: [],
  holdingId,
  createdByAnAI: false,
  createdAt: '2026-11-17T09:00:00+00:00',
  modifiedAt: '2026-11-17T09:00:00+00:00',
  _links: { self: { href: `/api/organizations/${id}`, method: 'GET' } },
})

const groupeAcme = anOrganization(GROUPE_ACME, 'Groupe ACME', null)
const acmeEst = anOrganization(ACME_EST, 'ACME Est', GROUPE_ACME)

type Call = { method: string; pathname: string; body: unknown }

function theApiAnswers() {
  const calls: Call[] = []
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const { pathname } = new URL(request.url)
    const body = request.method === 'GET' ? null : await request.text()
    calls.push({ method: request.method, pathname, body: body ? JSON.parse(body) : null })
    if (request.method === 'POST' && pathname === '/api/organizations') {
      return Response.json(acmeEst, { status: 201 })
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
      '/api/tags': { families: [], own: [], automatic: [] },
      '/api/organizations': { items: [groupeAcme], _links: {} },
      [`/api/organizations/${GROUPE_ACME}`]: groupeAcme,
      [`/api/organizations/${ACME_EST}`]: acmeEst,
      [`/api/organizations/${ACME_EST}/tags`]: { tags: [], _links: {} },
    }
    return Response.json(answers[pathname] ?? { items: [], _links: {} })
  })
  return calls
}

describe('starting to prospect a subsidiary', () => {
  afterEach(() => theApi.mockReset())

  it('sends one request, with the holding, and the sheet shows the holding', async () => {
    const calls = theApiAnswers()
    await renderTheSpaAt('/organizations/new')

    await userEvent.type(await screen.findByLabelText('Raison sociale'), 'ACME Est')
    await userEvent.type(screen.getByLabelText(/^Holding/), 'Groupe')
    await userEvent.click(await screen.findByRole('button', { name: 'Groupe ACME' }))
    await userEvent.click(screen.getByRole('button', { name: 'Commencer à prospecter' }))

    await screen.findByRole('heading', { name: 'ACME Est' }, THE_PAGE_OF_THE_ORGANIZATION_LOADED)
    expect(calls.filter((call) => call.method !== 'GET')).toEqual([
      {
        method: 'POST',
        pathname: '/api/organizations',
        body: expect.objectContaining({
          legalIdentity: expect.objectContaining({ corporateName: 'ACME Est' }),
          holdingId: GROUPE_ACME,
        }),
      },
    ])
    await waitFor(() => expect(screen.getByRole('link', { name: 'Groupe ACME' })).toBeInTheDocument())
  })
})
