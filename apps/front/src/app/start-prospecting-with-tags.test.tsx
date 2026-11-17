import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Organization } from '@/features/organizations/queries'
import type { SuggestedTags } from '@/features/tags/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** The creation of an organization on the routes of the SPA: its tags put once it is created. */

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

const ACME = '0190a1c0-0000-7000-8000-000000000001'

const me: Account = {
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

const acme: Organization = {
  id: ACME,
  name: 'QA campagnes',
  legalIdentity: { corporateName: 'QA campagnes' },
  contactDetails: {},
  contactIds: [],
  createdAt: '2026-11-17T09:00:00+00:00',
  modifiedAt: '2026-11-17T09:00:00+00:00',
  _links: { self: { href: `/api/organizations/${ACME}`, method: 'GET' } },
}

const suggested: SuggestedTags = {
  families: [
    { family: 'organizationType', tags: ['#APPORTEUR', '#ESN'] },
    { family: 'relationship', tags: [] },
    { family: 'technology', tags: [] },
  ],
  own: [],
}

type Call = { method: string; pathname: string; body: unknown }

function theApiAnswers() {
  const calls: Call[] = []
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const { pathname } = new URL(request.url)
    const body = request.method === 'GET' ? null : await request.text()
    calls.push({ method: request.method, pathname, body: body ? JSON.parse(body) : null })
    const answers: Record<string, unknown> = {
      '/api/me': me,
      '/api/tags': suggested,
      '/api/organizations': acme,
      [`/api/organizations/${ACME}`]: acme,
      [`/api/organizations/${ACME}/tags`]: { tags: [], _links: {} },
    }
    return Response.json(answers[pathname] ?? { items: [], _links: {} })
  })
  return calls
}

describe('starting to prospect an organization', () => {
  afterEach(() => theApi.mockReset())

  it('puts the tags chosen in the form on the organization once it is created', async () => {
    const calls = theApiAnswers()
    await renderTheSpaAt('/organizations/new')

    await userEvent.type(await screen.findByLabelText('Raison sociale'), 'QA campagnes')
    await userEvent.click(screen.getByRole('button', { name: 'Ajouter un tag' }))
    await userEvent.click(await screen.findByRole('option', { name: '#ESN' }))
    expect(screen.getByText('#ESN')).toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: 'Commencer à prospecter' }))

    await waitFor(() =>
      expect(calls).toContainEqual({ method: 'POST', pathname: `/api/organizations/${ACME}/tags`, body: { tags: ['#ESN'] } }),
    )
    const creation = calls.findIndex((call) => call.method === 'POST' && call.pathname === '/api/organizations')
    const tagging = calls.findIndex((call) => call.method === 'POST' && call.pathname.endsWith('/tags'))
    expect(creation).toBeLessThan(tagging)
  })

  it('puts no tag when none was chosen', async () => {
    const calls = theApiAnswers()
    await renderTheSpaAt('/organizations/new')

    await userEvent.type(await screen.findByLabelText('Raison sociale'), 'QA campagnes')
    await userEvent.click(screen.getByRole('button', { name: 'Commencer à prospecter' }))

    await screen.findByRole('heading', { name: 'QA campagnes' })
    expect(calls.filter((call) => call.method === 'POST' && call.pathname.endsWith('/tags'))).toEqual([])
  })
})
