import { act, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { toast } from 'sonner'
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
    signOutEverywhere: async () => {},
  },
}))

const CAMPAIGN = '0190a1c0-0000-7000-8000-00000000ca00'
const ADA = '0190a1c0-0000-7000-8000-000000000001'
const ALAN = '0190a1c0-0000-7000-8000-000000000003'
const GRACE = '0190a1c0-0000-7000-8000-000000000002'
const GRACE_EXCHANGE = '0190a1c0-0000-7000-8000-000000000e02'

const me: Account = {
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' }, campaigns: { href: '/api/campaigns', method: 'GET' } },
}

const started: Campaign = {
  id: CAMPAIGN,
  name: 'Appels ESN',
  channel: 'call',
  tags: ['#ESN'],
  targetedTags: ['#ESN', '#PHONE'],
  message: 'Pitch pour {firstName}',
  subject: null,
  messageMode: 'asIs',
  status: 'started',
  author: { name: { firstName: 'Alan', lastName: 'Turing' }, email: null },
  progress: { processed: 1, dismissed: 0 },
  targeted: 3,
  startedAt: '2026-11-16T17:00:00+00:00',
  stoppedAt: null,
  endedAt: null,
  _links: {
    self: { href: `/api/campaigns/${CAMPAIGN}`, method: 'GET' },
    stop: { href: `/api/campaigns/${CAMPAIGN}/stop`, method: 'POST' },
    recipients: { href: `/api/campaigns/${CAMPAIGN}/recipients`, method: 'GET' },
  },
}

const aContact = (id: string, firstName: string, lastName: string, phoneNumber: string): TargetedContact => ({
  id,
  title: 'ms',
  name: { firstName, lastName },
  jobTitle: null,
  contactDetails: { email: null, phoneNumber, language: null, links: [] },
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
      _links: { exchange: { href: `/api/exchanges/${GRACE_EXCHANGE}`, method: 'GET' } },
    },
    pending(ADA, 'Ada', 'Lovelace', '+33 6 12 34 56 78'),
    pending(ALAN, 'Alan', 'Turing', '+33 6 98 76 54 32'),
  ],
  _links: { self: { href: `/api/campaigns/${CAMPAIGN}/recipients`, method: 'GET' } },
}

type Call = { method: string; pathname: string; body: unknown }

function theApiAnswers({
  refusing,
  campaign = started,
  toLead = recipients,
}: {
  refusing?: string
  campaign?: Campaign
  toLead?: RecipientList
} = {}) {
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
      [`/api/campaigns/${CAMPAIGN}`]: campaign,
      [`/api/campaigns/${CAMPAIGN}/recipients`]: toLead,
      [`/api/campaigns/${CAMPAIGN}/messages/${ADA}`]: { subject: null, text: 'Pitch pour Ada' },
      [`/api/campaigns/${CAMPAIGN}/messages/${ALAN}`]: { subject: null, text: 'Pitch pour Alan' },
    }
    return Response.json(answers[pathname] ?? { items: [], _links: {} })
  })
  return calls
}

describe('a started campaign', () => {
  afterEach(() => {
    act(() => toast.dismiss())
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

  it('offers the exchange noted for a recipient processed, the others none', async () => {
    theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    const list = await screen.findByRole('list', { name: 'Destinataires' })
    expect(
      within(list).getByRole('button', { name: 'Ouvrir l’échange avec Grace Hopper' }),
    ).toBeInTheDocument()
    expect(
      within(list).queryByRole('button', { name: 'Ouvrir l’échange avec Ada Lovelace' }),
    ).not.toBeInTheDocument()
  })

  it('copies the message rendered for the recipient', async () => {
    theApiAnswers()
    const user = userEvent.setup()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await user.click(await screen.findByRole('button', { name: 'Copier' }))

    expect(await navigator.clipboard.readText()).toBe('Pitch pour Ada')
  })

  it('says the message is copied', async () => {
    theApiAnswers()
    const user = userEvent.setup()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await user.click(await screen.findByRole('button', { name: 'Copier' }))

    expect(await screen.findByText('Message copié.')).toBeInTheDocument()
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

  it('says it is ended once nobody is left to handle', async () => {
    theApiAnswers({
      toLead: { ...recipients, items: [pending(ADA, 'Ada', 'Lovelace', '+33 6 12 34 56 78')] },
    })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)
    await screen.findByText('Pitch pour Ada')

    await userEvent.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(
      await screen.findByText('Plus personne n’est à traiter : la campagne est terminée.'),
    ).toBeInTheDocument()
  })

  it('says it is ended once Campaigns ended it', async () => {
    theApiAnswers({
      campaign: { ...started, status: 'ended', endedAt: '2026-11-17T09:00:00+00:00' },
      toLead: { ...recipients, items: recipients.items.slice(0, 1) },
    })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    expect(
      await screen.findByText('Plus personne n’est à traiter : la campagne est terminée.'),
    ).toBeInTheDocument()
  })

  it('does not say a stopped campaign is ended', async () => {
    theApiAnswers({ campaign: { ...started, status: 'stopped', stoppedAt: '2026-11-17T09:00:00+00:00' } })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)
    await screen.findByText('Arrêtée')

    expect(
      screen.queryByText('Plus personne n’est à traiter : la campagne est terminée.'),
    ).not.toBeInTheDocument()
  })

  it('says how an AI sent the message of a campaign no longer led', async () => {
    theApiAnswers({
      campaign: {
        ...started,
        status: 'stopped',
        messageMode: 'adapted',
        stoppedAt: '2026-11-17T09:00:00+00:00',
      },
    })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    expect(await screen.findByText("Message pour l'IA")).toBeInTheDocument()
    expect(screen.getByText('Adapté à chaque personne')).toBeInTheDocument()
  })

  it('says its key moves to the next recipient', async () => {
    theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    expect(await screen.findByText('La touche N passe au destinataire suivant.')).toBeInTheDocument()
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
