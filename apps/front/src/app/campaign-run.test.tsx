import { screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Campaign, Recipient, RecipientList, TargetedContact } from '@/features/campaigns/queries'
import { AFTER_THE_REACTIONS_MS } from '@/shared/api/reactions'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** A started campaign led contact by contact (ADR 0145): a recipient chosen, reached, then noted or dismissed. */

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

const CAMPAIGN = '0190a1c0-0000-7000-8000-00000000ca00'
const ADA = '0190a1c0-0000-7000-8000-000000000001'
const ALAN = '0190a1c0-0000-7000-8000-000000000003'
const GRACE = '0190a1c0-0000-7000-8000-000000000002'

const me: Account = {
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' }, campaigns: { href: '/api/campaigns', method: 'GET' } },
}

const started: Campaign = {
  id: CAMPAIGN,
  name: 'Appels ESN',
  channel: 'call',
  tags: ['#ESN'],
  targetedTags: ['#ESN', '#PHONE'],
  message: 'Pitch pour {prénom}',
  subject: null,
  status: 'started',
  author: { name: { firstName: 'Alan', lastName: 'Turing' }, email: null },
  progress: { processed: 1, dismissed: 0 },
  targeted: 3,
  startedAt: '2026-11-16T17:00:00+00:00',
  stoppedAt: null,
  endedAt: null,
  _links: {
    self: { href: `/api/campaigns/${CAMPAIGN}`, method: 'GET' },
    stop: { href: `/api/campaigns/${CAMPAIGN}/stopping`, method: 'POST' },
    recipients: { href: `/api/campaigns/${CAMPAIGN}/recipients`, method: 'GET' },
  },
}

const aContact = (id: string, firstName: string, lastName: string, phoneNumber: string): TargetedContact => ({
  id,
  civility: 'ms',
  name: { firstName, lastName },
  jobTitle: null,
  contactDetails: { email: null, phoneNumber, language: null },
  masked: false,
  organizations: [
    {
      id: '0190a1c0-0000-7000-8000-0000000000aa',
      name: 'ACME',
      organization: { href: '/api/organizations/0190a1c0-0000-7000-8000-0000000000aa', method: 'GET' },
    },
  ],
  tags: ['#ESN', '#PHONE'],
  contact: { href: `/api/contacts/${id}`, method: 'GET' },
})

const pending = (id: string, firstName: string, lastName: string, phoneNumber: string): Recipient => ({
  contact: aContact(id, firstName, lastName, phoneNumber),
  status: 'pending',
  missed: false,
  reason: null,
  _links: {
    noteExchange: { href: `/api/campaigns/${CAMPAIGN}/recipients/${id}/exchanges`, method: 'POST' },
    dismiss: { href: `/api/campaigns/${CAMPAIGN}/recipients/${id}/dismissal`, method: 'POST' },
  },
})

const recipients: RecipientList = {
  items: [
    {
      contact: aContact(GRACE, 'Grace', 'Hopper', '+33 6 11 22 33 44'),
      status: 'processed',
      missed: true,
      reason: null,
      _links: {},
    },
    pending(ADA, 'Ada', 'Lovelace', '+33 6 12 34 56 78'),
    pending(ALAN, 'Alan', 'Turing', '+33 6 98 76 54 32'),
  ],
  _links: { self: { href: `/api/campaigns/${CAMPAIGN}/recipients`, method: 'GET' } },
}

type Call = { method: string; pathname: string; body: unknown }

function theApiAnswers({ refusing }: { refusing?: string } = {}) {
  const calls: Call[] = []
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const { pathname } = new URL(request.url)
    const body = request.method === 'GET' ? null : await request.text()
    calls.push({ method: request.method, pathname, body: body ? JSON.parse(body) : null })
    if (request.method === 'POST' && refusing) {
      return Response.json(
        { type: 'about:blank', title: 'Conflict', status: 409, detail: 'refused', reason: refusing },
        { status: 409, headers: { 'Content-Type': 'application/problem+json' } },
      )
    }
    if (request.method === 'POST' && pathname.endsWith('/exchanges')) {
      return Response.json({ items: [], _links: {} }, { status: 201 })
    }
    if (request.method === 'POST') {
      return new Response(null, { status: 204 })
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
      [`/api/campaigns/${CAMPAIGN}`]: started,
      [`/api/campaigns/${CAMPAIGN}/recipients`]: recipients,
      [`/api/campaigns/${CAMPAIGN}/messages/${ADA}`]: { subject: null, text: 'Pitch pour Ada' },
      [`/api/campaigns/${CAMPAIGN}/messages/${ALAN}`]: { subject: null, text: 'Pitch pour Alan' },
    }
    return Response.json(answers[pathname] ?? { items: [], _links: {} })
  })
  return calls
}

describe('a started campaign', () => {
  afterEach(() => {
    vi.useRealTimers()
    theApi.mockReset()
  })

  it('shows its progress, its recipients to handle first, and opens the first of them', async () => {
    theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    expect(await screen.findByText('1 / 3')).toBeInTheDocument()
    const lines = within(screen.getByRole('list', { name: 'Destinataires' })).getAllByRole('listitem')
    expect(lines.map((line) => line.textContent)).toEqual([
      expect.stringContaining('Ada Lovelace'),
      expect.stringContaining('Alan Turing'),
      expect.stringContaining('Grace Hopper'),
    ])
    expect(within(lines[2] as HTMLElement).getByText('Manqué')).toBeInTheDocument()
    expect(await screen.findByRole('heading', { name: 'Ada Lovelace' })).toBeInTheDocument()
    expect(screen.getByText('+33 6 12 34 56 78')).toBeInTheDocument()
    expect(await screen.findByText('Pitch pour Ada')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Ouvrir' })).toHaveAttribute('href', 'tel:+33612345678')
    expect(screen.getByText('Appel · Émis')).toBeInTheDocument()
    expect(screen.getByLabelText(/^Texte/)).toHaveValue('Pitch pour Ada')
  })

  it('copies the message rendered for the recipient', async () => {
    theApiAnswers()
    const user = userEvent.setup()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await user.click(await screen.findByRole('button', { name: 'Copier' }))

    expect(await navigator.clipboard.readText()).toBe('Pitch pour Ada')
  })

  it('notes the call of the recipient, missed, then moves to the next one to handle', async () => {
    const calls = theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)
    await screen.findByText('Pitch pour Ada')

    await userEvent.click(screen.getByLabelText(/Manqué/))
    await userEvent.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await waitFor(() =>
      expect(calls.find((call) => call.method === 'POST')).toMatchObject({
        pathname: `/api/campaigns/${CAMPAIGN}/recipients/${ADA}/exchanges`,
        body: { text: 'Pitch pour Ada', missed: true },
      }),
    )
    expect(await screen.findByRole('heading', { name: 'Alan Turing' })).toBeInTheDocument()
    const ada = within(screen.getByRole('list', { name: 'Destinataires' }))
      .getByText('Ada Lovelace')
      .closest('li') as HTMLElement
    expect(within(ada).getByText('Traité')).toBeInTheDocument()
  })

  it('reads the campaign again once the reactions processed the recipient', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    const calls = theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)
    await screen.findByText('Pitch pour Ada')
    const readings = () =>
      calls.filter((call) => call.method === 'GET' && call.pathname === `/api/campaigns/${CAMPAIGN}`).length

    await userEvent.click(screen.getByRole('button', { name: 'Enregistrer' }))
    await screen.findByRole('heading', { name: 'Alan Turing' })
    const before = readings()
    await vi.advanceTimersByTimeAsync(AFTER_THE_REACTIONS_MS)

    await waitFor(() => expect(readings()).toBeGreaterThan(before))
    vi.useRealTimers()
  })

  it('dismisses the recipient for the reason typed, then moves to the next one', async () => {
    const calls = theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)
    await screen.findByText('Pitch pour Ada')

    await userEvent.type(screen.getByLabelText(/^Motif de l’écart/), 'Parti de la société')
    await userEvent.click(screen.getByRole('button', { name: 'Écarter' }))

    await waitFor(() =>
      expect(calls.find((call) => call.method === 'POST')).toMatchObject({
        pathname: `/api/campaigns/${CAMPAIGN}/recipients/${ADA}/dismissal`,
        body: { reason: 'Parti de la société' },
      }),
    )
    expect(await screen.findByRole('heading', { name: 'Alan Turing' })).toBeInTheDocument()
  })

  it('moves to the next recipient by its key, without noting anything', async () => {
    const calls = theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)
    await screen.findByRole('heading', { name: 'Ada Lovelace' })

    await userEvent.keyboard('n')

    expect(await screen.findByRole('heading', { name: 'Alan Turing' })).toBeInTheDocument()
    expect(calls.filter((call) => call.method === 'POST')).toEqual([])
  })

  it('opens a recipient chosen in the list', async () => {
    theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)
    await screen.findByRole('heading', { name: 'Ada Lovelace' })

    await userEvent.click(
      within(screen.getByRole('list', { name: 'Destinataires' })).getByRole('button', {
        name: /Alan Turing/,
      }),
    )

    expect(await screen.findByRole('heading', { name: 'Alan Turing' })).toBeInTheDocument()
    expect(await screen.findByText('Pitch pour Alan')).toBeInTheDocument()
  })

  it('says why the API refuses, the recipient kept open', async () => {
    theApiAnswers({ refusing: 'recipientAlreadyProcessed' })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)
    await screen.findByText('Pitch pour Ada')

    await userEvent.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('Ce destinataire est déjà traité.')
    expect(screen.getByRole('heading', { name: 'Ada Lovelace' })).toBeInTheDocument()
  })
})
