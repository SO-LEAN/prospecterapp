import { screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Organization } from '@/features/organizations/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** « Nouveau », at the top of every page of the shell, on the SPA's routes: it opens the creation forms that exist. */

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
  name: { firstName: 'Alan', lastName: 'Turing' },
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

/** A member who manages the personal data: my account offers the campaigns, the contacts list offers to collect one. */
const aManagerOfThePersonalData = {
  '/api/me': { ...me, _links: { ...me._links, campaigns: { href: '/api/campaigns', method: 'GET' } } },
  '/api/contacts': {
    items: [],
    _links: {
      self: { href: '/api/contacts', method: 'GET' },
      collect: { href: '/api/contacts', method: 'POST' },
    },
  },
}

const ACME = '0190a1c0-0000-7000-8000-00000000a000'

const acme: Organization = {
  id: ACME,
  revision: 1,
  name: 'QA nouveau',
  legalIdentity: { corporateName: 'QA nouveau' },
  contactDetails: { links: [] },
  contactIds: [],
  possibleDuplicates: [],
  createdByAnAI: false,
  createdAt: '2026-11-17T09:00:00+00:00',
  modifiedAt: '2026-11-17T09:00:00+00:00',
  _links: { self: { href: `/api/organizations/${ACME}`, method: 'GET' } },
}

type Sent = { method: string; path: string; body: unknown }

/** The API serves my account, the shell's reads and the answers given, by path or by `POST <path>`; any other read finds nothing. */
function theApiAnswers(answers: Record<string, unknown> = {}) {
  const sent: Sent[] = []
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const url = new URL(request.url)
    if (request.method !== 'GET') {
      sent.push({ method: request.method, path: url.pathname, body: await request.json() })
      return Response.json(answers[`${request.method} ${url.pathname}`] ?? {}, { status: 201 })
    }
    const read: Record<string, unknown> = {
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
      ...answers,
    }
    return Response.json(read[url.pathname] ?? { items: [], _links: {} })
  })
  return sent
}

describe('« Nouveau »', () => {
  afterEach(() => theApi.mockReset())

  it.each(['/', '/contacts', '/calendar', '/campaigns', '/settings'])('is at the top of %s', async (path) => {
    theApiAnswers()
    await renderTheSpaAt(path)

    expect(await screen.findByRole('button', { name: 'Nouveau' })).toBeInTheDocument()
  })

  it('offers the creations that exist, in this order', async () => {
    theApiAnswers(aManagerOfThePersonalData)
    await renderTheSpaAt('/')

    await userEvent.click(await screen.findByRole('button', { name: 'Nouveau' }))

    await screen.findByRole('menuitem', { name: 'Nouveau contact' })
    const entries = screen.getAllByRole('menuitem')
    expect(entries.map((entry) => entry.textContent)).toEqual([
      'Noter un échange',
      'Planifier un échange',
      'Nouveau contact',
      'Commencer à prospecter',
      'Nouvelle campagne',
    ])
  })

  it('offers no new campaign to a member whose account does not offer the campaigns', async () => {
    theApiAnswers({ ...aManagerOfThePersonalData, '/api/me': me })
    await renderTheSpaAt('/')

    await userEvent.click(await screen.findByRole('button', { name: 'Nouveau' }))

    expect(await screen.findByRole('menuitem', { name: 'Nouveau contact' })).toBeInTheDocument()
    expect(screen.queryByRole('menuitem', { name: 'Nouvelle campagne' })).not.toBeInTheDocument()
  })

  it('offers no new contact to a member the contacts list does not offer to collect one', async () => {
    theApiAnswers({
      ...aManagerOfThePersonalData,
      '/api/contacts': { items: [], _links: { self: { href: '/api/contacts', method: 'GET' } } },
    })
    await renderTheSpaAt('/')

    await userEvent.click(await screen.findByRole('button', { name: 'Nouveau' }))

    expect(await screen.findByRole('menuitem', { name: 'Nouvelle campagne' })).toBeInTheDocument()
    expect(screen.queryByRole('menuitem', { name: 'Nouveau contact' })).not.toBeInTheDocument()
  })

  it('opens the form to start prospecting, which sends one request, as from the organizations list', async () => {
    const sent = theApiAnswers({ 'POST /api/organizations': acme, [`/api/organizations/${ACME}`]: acme })
    const router = await renderTheSpaAt('/calendar')

    await userEvent.click(await screen.findByRole('button', { name: 'Nouveau' }))
    await userEvent.click(await screen.findByRole('menuitem', { name: 'Commencer à prospecter' }))
    await userEvent.type(await screen.findByLabelText('Raison sociale'), 'QA nouveau')
    await userEvent.click(screen.getByRole('button', { name: 'Commencer à prospecter' }))

    expect(await screen.findByRole('heading', { name: 'QA nouveau' })).toBeInTheDocument()
    expect(router.state.location.pathname).toBe(`/organizations/${ACME}`)
    expect(sent).toEqual([
      expect.objectContaining({
        method: 'POST',
        path: '/api/organizations',
        body: expect.objectContaining({
          legalIdentity: expect.objectContaining({ corporateName: 'QA nouveau' }),
        }),
      }),
    ])
  })

  it('leads to the forms of a new contact and of a new campaign', async () => {
    theApiAnswers(aManagerOfThePersonalData)
    await renderTheSpaAt('/')

    await userEvent.click(await screen.findByRole('button', { name: 'Nouveau' }))

    expect(await screen.findByRole('menuitem', { name: 'Nouveau contact' })).toHaveAttribute(
      'href',
      '/contacts/new',
    )
    expect(screen.getByRole('menuitem', { name: 'Nouvelle campagne' })).toHaveAttribute(
      'href',
      '/campaigns/new',
    )
  })

  it('notes an exchange from the calendar with the organization chosen first by the search', async () => {
    const timeline = {
      items: [],
      toRedact: 0,
      _links: {
        self: { href: `/api/organizations/${ACME}/exchanges`, method: 'GET' },
        noteExchange: { href: `/api/organizations/${ACME}/exchanges`, method: 'POST' },
      },
    }
    const sent = theApiAnswers({
      '/api/organizations': { items: [{ id: ACME, name: 'QA nouveau', possibleDuplicates: [] }], _links: {} },
      [`/api/organizations/${ACME}/exchanges`]: timeline,
      [`POST /api/organizations/${ACME}/exchanges`]: timeline,
    })
    await renderTheSpaAt('/calendar')

    await userEvent.click(await screen.findByRole('button', { name: 'Nouveau' }))
    await userEvent.click(await screen.findByRole('menuitem', { name: 'Noter un échange' }))
    const dialog = await screen.findByRole('dialog', { name: 'Noter un échange' })
    await userEvent.type(within(dialog).getByRole('searchbox'), 'QA')
    await userEvent.click(await within(dialog).findByRole('button', { name: 'QA nouveau' }))
    await userEvent.click(await within(dialog).findByRole('radio', { name: 'Appel' }))
    await userEvent.click(within(dialog).getByRole('button', { name: 'Enregistrer' }))

    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument())
    expect(sent).toEqual([
      expect.objectContaining({ method: 'POST', path: `/api/organizations/${ACME}/exchanges` }),
    ])
  })

  it('opens the planning of an exchange with someone chosen first by the search', async () => {
    theApiAnswers()
    await renderTheSpaAt('/campaigns')

    await userEvent.click(await screen.findByRole('button', { name: 'Nouveau' }))
    await userEvent.click(await screen.findByRole('menuitem', { name: 'Planifier un échange' }))

    const dialog = await screen.findByRole('dialog', { name: 'Planifier un échange' })
    expect(
      within(dialog).getByRole('searchbox', { name: 'Chercher un contact ou une organisation' }),
    ).toBeInTheDocument()
  })
})
