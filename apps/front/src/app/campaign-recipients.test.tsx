import { screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Campaign, RecipientList, TargetedContact } from '@/features/campaigns/queries'
import type { ExchangeDetail } from '@/features/exchanges/queries'
import type { SuggestedTags } from '@/features/tags/queries'
import { AFTER_THE_REACTIONS_MS } from '@/shared/api/reactions'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** The recipients of a campaign (ADR 0144): a draft frozen once confirmed, then its list changed by hand. */

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
const GRACE = '0190a1c0-0000-7000-8000-000000000002'
const ALAN = '0190a1c0-0000-7000-8000-000000000003'
const EXCHANGE = '0190a1c0-0000-7000-8000-000000000e01'

const me: Account = {
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' }, campaigns: { href: '/api/campaigns', method: 'GET' } },
}

const self = { href: `/api/campaigns/${CAMPAIGN}`, method: 'GET' }

const draft: Campaign = {
  id: CAMPAIGN,
  name: 'Relance ESN',
  channel: 'email',
  tags: ['#ESN'],
  targetedTags: ['#ESN', '#EMAIL'],
  message: 'Bonjour {prénom}',
  subject: null,
  messageMode: 'asIs',
  status: 'draft',
  author: { name: { firstName: 'Alan', lastName: 'Turing' }, email: null },
  progress: { processed: 0, dismissed: 0 },
  targeted: 2,
  startedAt: null,
  stoppedAt: null,
  endedAt: null,
  _links: {
    self,
    revise: { href: `/api/campaigns/${CAMPAIGN}`, method: 'PATCH' },
    discard: { href: `/api/campaigns/${CAMPAIGN}`, method: 'DELETE' },
    freeze: { href: `/api/campaigns/${CAMPAIGN}/freezing`, method: 'POST' },
    target: { href: '/api/tagged-contacts?tags=ESN,EMAIL', method: 'GET' },
  },
}

const frozen: Campaign = {
  ...draft,
  status: 'frozen',
  _links: {
    self,
    revise: { href: `/api/campaigns/${CAMPAIGN}`, method: 'PATCH' },
    discard: { href: `/api/campaigns/${CAMPAIGN}`, method: 'DELETE' },
    addRecipient: { href: `/api/campaigns/${CAMPAIGN}/recipients`, method: 'POST' },
    addRecipientsByTags: { href: `/api/campaigns/${CAMPAIGN}/recipients/by-tags`, method: 'POST' },
    start: { href: `/api/campaigns/${CAMPAIGN}/starting`, method: 'POST' },
    recipients: { href: `/api/campaigns/${CAMPAIGN}/recipients`, method: 'GET' },
  },
}

const started: Campaign = {
  ...frozen,
  status: 'started',
  startedAt: '2026-11-16T17:00:00+00:00',
  _links: {
    self,
    stop: { href: `/api/campaigns/${CAMPAIGN}/stopping`, method: 'POST' },
    recipients: { href: `/api/campaigns/${CAMPAIGN}/recipients`, method: 'GET' },
  },
}

const aContact = (id: string, firstName: string, lastName: string): TargetedContact => ({
  id,
  name: { firstName, lastName },
  title: 'x',
  jobTitle: null,
  contactDetails: { email: `${firstName}@acme.test`, phoneNumber: null, language: null, links: [] },
  masked: false,
  organizations: [],
  tags: ['#ESN', '#EMAIL'],
  contact: { href: `/api/contacts/${id}`, method: 'GET' },
})

const recipients: RecipientList = {
  items: [ADA, GRACE].map((id, index) => ({
    contact: aContact(id, index === 0 ? 'Ada' : 'Grace', index === 0 ? 'Lovelace' : 'Hopper'),
    status: 'pending' as const,
    missed: false,
    reason: null,
    _links: { remove: { href: `/api/campaigns/${CAMPAIGN}/recipients/${id}`, method: 'DELETE' } },
  })),
  _links: { self: { href: `/api/campaigns/${CAMPAIGN}/recipients`, method: 'GET' } },
}

const suggested: SuggestedTags = {
  families: [
    { family: 'organizationType', tags: ['#ESN', '#EDITEUR'] },
    { family: 'relationship', tags: [] },
    { family: 'technology', tags: [] },
  ],
  own: [],
  automatic: ['#EMAIL', '#PHONE', '#SMS', '#LINKEDIN'],
}

const theExchangeOfAda: ExchangeDetail = {
  id: EXCHANGE,
  interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
  channel: 'email',
  direction: 'outbound',
  canceller: null,
  occurredAt: '2026-11-17T09:00:00+00:00',
  status: 'done',
  durationInMinutes: null,
  text: 'Relancée pour la mission',
  place: null,
  link: null,
  linkAwaited: false,
  phoneNumber: null,
  textFlagged: false,
  textToRedact: false,
  toRedact: false,
  organizationIds: [],
  noted: { at: '2026-11-17T09:05:00+00:00', by: { name: { firstName: 'Grace', lastName: 'Hopper' } } },
  corrected: null,
  redacted: null,
  attachments: [],
  hiddenAttachments: 0,
  bookedByLink: false,
  createdByAnAI: false,
  campaign: { id: CAMPAIGN, name: 'Relance ESN' },
  _links: {
    self: { href: `/api/exchanges/${EXCHANGE}`, method: 'GET' },
    eraseExchange: { href: `/api/exchanges/${EXCHANGE}`, method: 'DELETE' },
  },
}

const adaProcessed: RecipientList = {
  items: [
    {
      contact: aContact(ADA, 'Ada', 'Lovelace'),
      status: 'processed',
      missed: false,
      reason: null,
      _links: { exchange: { href: `/api/exchanges/${EXCHANGE}`, method: 'GET' } },
    },
  ],
  _links: { self: { href: `/api/campaigns/${CAMPAIGN}/recipients`, method: 'GET' } },
}

type Call = { method: string; pathname: string; search: string; body: unknown }

const twoContacts = {
  items: [aContact(ADA, 'Ada', 'Lovelace'), aContact(ALAN, 'Alan', 'Turing')],
  found: 2,
  _links: {},
}

function theApiAnswers(
  campaign: Campaign,
  taggedContacts: unknown = twoContacts,
  keeping: RecipientList = recipients,
) {
  const calls: Call[] = []
  let kept = keeping
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const { pathname, search } = new URL(request.url)
    const body = request.method === 'GET' || request.method === 'DELETE' ? null : await request.text()
    calls.push({ method: request.method, pathname, search, body: body ? JSON.parse(body) : null })
    if (request.method === 'DELETE') {
      kept = {
        ...kept,
        items: kept.items.filter((recipient) => !pathname.endsWith(recipient.contact?.id ?? '')),
      }
      return new Response(null, { status: 204 })
    }
    if (request.method === 'POST') {
      return Response.json(frozen)
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
      '/api/tags': suggested,
      [`/api/campaigns/${CAMPAIGN}`]: campaign,
      [`/api/campaigns/${CAMPAIGN}/recipients`]: kept,
      '/api/tagged-contacts': taggedContacts,
      '/api/contacts': { items: [aContact(ALAN, 'Alan', 'Turing')], _links: {} },
      [`/api/campaigns/${CAMPAIGN}/messages/${ADA}`]: { subject: null, text: 'Bonjour Ada' },
      [`/api/campaigns/${CAMPAIGN}/messages/${GRACE}`]: { subject: null, text: 'Bonjour Grace' },
      [`/api/exchanges/${EXCHANGE}`]: theExchangeOfAda,
    }
    return Response.json(answers[pathname] ?? { items: [], _links: {} })
  })
  return calls
}

describe('the recipients of a campaign', () => {
  afterEach(() => theApi.mockReset())

  it('are frozen from a draft once the member confirms its search no longer changes', async () => {
    const calls = theApiAnswers(draft)
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await userEvent.click(await screen.findByRole('button', { name: 'Figer la liste' }))
    const confirmation = await screen.findByRole('dialog')
    expect(within(confirmation).getByText(/La recherche ne pourra plus changer/)).toBeInTheDocument()
    expect(calls.filter((call) => call.method === 'POST')).toEqual([])

    await userEvent.click(within(confirmation).getByRole('button', { name: 'Figer la liste' }))

    await waitFor(() =>
      expect(calls).toContainEqual(
        expect.objectContaining({ method: 'POST', pathname: `/api/campaigns/${CAMPAIGN}/freezing` }),
      ),
    )
    expect(await screen.findByRole('heading', { name: 'Destinataires' })).toBeInTheDocument()
  })

  it('start once the member confirms its list and its message will no longer change', async () => {
    const calls = theApiAnswers(frozen)
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await userEvent.click(await screen.findByRole('button', { name: 'Démarrer la campagne' }))
    const confirmation = await screen.findByRole('dialog')
    expect(
      within(confirmation).getByText(/La liste et le message ne pourront plus changer/),
    ).toBeInTheDocument()
    expect(calls.filter((call) => call.method === 'POST')).toEqual([])

    await userEvent.click(within(confirmation).getByRole('button', { name: 'Démarrer la campagne' }))

    await waitFor(() =>
      expect(calls).toContainEqual(
        expect.objectContaining({ method: 'POST', pathname: `/api/campaigns/${CAMPAIGN}/starting` }),
      ),
    )
  })

  it('no longer change once started: neither cross, nor « Ajouter », nor a form to save, nor « Supprimer »', async () => {
    theApiAnswers(started, twoContacts, {
      ...recipients,
      items: recipients.items.map((recipient) => ({ ...recipient, _links: {} })),
    })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    const list = await screen.findByRole('list', { name: 'Destinataires' })
    expect(within(list).getByText('Ada Lovelace')).toBeInTheDocument()
    expect(screen.getByText('Démarrée')).toBeInTheDocument()
    expect(screen.getByText('Bonjour {prénom}')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /^Retirer/ })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Ajouter' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Enregistrer' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Démarrer la campagne' })).not.toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: /^Actions sur «\s*Relance ESN\s*»$/ }))
    expect(screen.queryByRole('menuitem', { name: 'Supprimer' })).not.toBeInTheDocument()
  })

  it('are kept when the campaign is stopped from its menu, once the member confirms', async () => {
    const calls = theApiAnswers(started)
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await userEvent.click(await screen.findByRole('button', { name: /^Actions sur «\s*Relance ESN\s*»$/ }))
    await userEvent.click(await screen.findByRole('menuitem', { name: 'Arrêter' }))
    const confirmation = await screen.findByRole('dialog')
    expect(within(confirmation).getByText(/Elle restera dans la liste des campagnes/)).toBeInTheDocument()

    await userEvent.click(within(confirmation).getByRole('button', { name: 'Arrêter la campagne' }))

    await waitFor(() =>
      expect(calls).toContainEqual(
        expect.objectContaining({ method: 'POST', pathname: `/api/campaigns/${CAMPAIGN}/stopping` }),
      ),
    )
  })

  it('name a contact erased from a started campaign « Contact effacé », no longer to handle', async () => {
    theApiAnswers(started, twoContacts, {
      ...recipients,
      items: [
        {
          contact: aContact(ADA, 'Ada', 'Lovelace'),
          status: 'pending',
          missed: false,
          reason: null,
          _links: {},
        },
        { contact: null, status: 'erased', missed: false, reason: null, _links: {} },
      ],
    })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    const list = await screen.findByRole('list', { name: 'Destinataires' })
    expect(within(list).getByText('Contact effacé')).toBeInTheDocument()
    expect(within(list).getByText('Plus à traiter')).toBeInTheDocument()
    expect(within(list).getByText('Ada Lovelace')).toBeInTheDocument()
  })

  it('tell what was done with a contact erased once processed: a call missed', async () => {
    theApiAnswers({ ...started, status: 'ended', endedAt: '2026-11-17T10:00:00+00:00' }, twoContacts, {
      ...recipients,
      items: [{ contact: null, status: 'processed', missed: true, reason: null, _links: {} }],
    })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    const list = await screen.findByRole('list', { name: 'Destinataires' })
    expect(within(list).getByText('Contact effacé')).toBeInTheDocument()
    expect(within(list).getByText('Manqué')).toBeInTheDocument()
  })

  it('open the exchange noted for a recipient processed', async () => {
    theApiAnswers(
      { ...started, status: 'ended', endedAt: '2026-11-17T10:00:00+00:00' },
      twoContacts,
      adaProcessed,
    )
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await userEvent.click(await screen.findByRole('button', { name: 'Ouvrir l’échange avec Ada Lovelace' }))

    const sheet = await screen.findByRole('dialog', { name: 'Échange' })
    expect(await within(sheet).findByText('Relancée pour la mission')).toBeInTheDocument()
  })

  it('read the campaign again once the reactions to the erasure of the exchange of a recipient ran', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    const calls = theApiAnswers(
      { ...started, status: 'ended', endedAt: '2026-11-17T10:00:00+00:00' },
      twoContacts,
      adaProcessed,
    )
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)
    await userEvent.click(await screen.findByRole('button', { name: 'Ouvrir l’échange avec Ada Lovelace' }))
    await userEvent.click(await screen.findByRole('button', { name: 'Supprimer' }))
    await userEvent.click(await screen.findByRole('button', { name: 'Supprimer l’échange' }))
    await waitFor(() =>
      expect(calls.find((call) => call.method === 'DELETE')).toMatchObject({
        pathname: `/api/exchanges/${EXCHANGE}`,
      }),
    )
    const readings = () =>
      calls.filter((call) => call.method === 'GET' && call.pathname === `/api/campaigns/${CAMPAIGN}`).length
    const before = readings()

    await vi.advanceTimersByTimeAsync(AFTER_THE_REACTIONS_MS)

    await waitFor(() => expect(readings()).toBeGreaterThan(before))
    vi.useRealTimers()
  })

  it('say once the campaign ended which were processed, by a missed call, and which were dismissed', async () => {
    theApiAnswers({ ...started, status: 'ended', endedAt: '2026-11-17T10:00:00+00:00' }, twoContacts, {
      ...recipients,
      items: [
        {
          contact: aContact(ADA, 'Ada', 'Lovelace'),
          status: 'processed',
          missed: true,
          reason: null,
          _links: {},
        },
        {
          contact: aContact(GRACE, 'Grace', 'Hopper'),
          status: 'dismissed',
          missed: false,
          reason: 'Partie',
          _links: {},
        },
      ],
    })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    const list = await screen.findByRole('list', { name: 'Destinataires' })
    expect(
      within(within(list).getByText('Ada Lovelace').closest('li') as HTMLElement).getByText('Manqué'),
    ).toBeInTheDocument()
    expect(
      within(within(list).getByText('Grace Hopper').closest('li') as HTMLElement).getByText('Écarté'),
    ).toBeInTheDocument()
    expect(screen.getByText('Terminée')).toBeInTheDocument()
  })

  it('are listed once frozen, to handle, its tags and its channel shown but no longer changed', async () => {
    theApiAnswers(frozen)
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    const list = await screen.findByRole('list', { name: 'Destinataires' })
    expect(within(list).getByText('Ada Lovelace')).toBeInTheDocument()
    expect(within(list).getAllByText('À traiter')).toHaveLength(2)
    expect(screen.getByText('2 destinataires')).toBeInTheDocument()
    expect(screen.getByText('#ESN')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Filtrer par tag' })).not.toBeInTheDocument()
    expect(screen.getByLabelText('Canal')).toBeDisabled()
    expect(screen.queryByRole('button', { name: 'Figer la liste' })).not.toBeInTheDocument()
  })

  it('say none is left once the erasure of its contacts emptied a frozen campaign, which no longer starts but is still discarded', async () => {
    const { start: _start, ...withoutStart } = frozen._links
    theApiAnswers({ ...frozen, targeted: 0, _links: withoutStart }, twoContacts, { ...recipients, items: [] })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    expect(
      await screen.findByText('Ses destinataires ont tous été effacés : la campagne ne démarre plus.'),
    ).toBeInTheDocument()
    expect(screen.getByText('0 destinataire')).toBeInTheDocument()
    expect(screen.queryByRole('list', { name: 'Destinataires' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Démarrer la campagne' })).not.toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: /^Actions sur «\s*Relance ESN\s*»$/ }))
    expect(screen.getByRole('menuitem', { name: 'Supprimer' })).toBeInTheDocument()
  })

  it('loses one by its cross', async () => {
    const calls = theApiAnswers(frozen)
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await userEvent.click(await screen.findByRole('button', { name: 'Retirer Grace Hopper' }))

    await waitFor(() =>
      expect(calls).toContainEqual(
        expect.objectContaining({
          method: 'DELETE',
          pathname: `/api/campaigns/${CAMPAIGN}/recipients/${GRACE}`,
        }),
      ),
    )
  })

  it('preview the message of a recipient still kept once the one previewed is taken off', async () => {
    theApiAnswers(frozen)
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    const list = await screen.findByRole('list', { name: 'Destinataires' })
    await userEvent.hover(within(list).getByRole('button', { name: 'Grace Hopper' }))
    expect(await screen.findByText('Bonjour Grace')).toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: 'Retirer Grace Hopper' }))

    expect(await screen.findByText('Bonjour Ada')).toBeInTheDocument()
    expect(screen.queryByText('Bonjour Grace')).not.toBeInTheDocument()
  })

  it('gain those of another search by tags, the preview leaving out those already kept', async () => {
    const calls = theApiAnswers(frozen)
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await userEvent.click(await screen.findByRole('button', { name: 'Ajouter' }))
    const dialog = await screen.findByRole('dialog', { name: 'Ajouter des destinataires' })
    await userEvent.click(within(dialog).getByRole('button', { name: 'Filtrer par tag' }))
    await userEvent.click(await screen.findByRole('option', { name: '#EDITEUR' }))

    expect(await within(dialog).findByText('1 contact sera ajouté')).toBeInTheDocument()
    expect(within(dialog).getByText('Alan Turing')).toBeInTheDocument()
    expect(within(dialog).queryByText('Ada Lovelace')).not.toBeInTheDocument()
    expect(calls).toContainEqual(
      expect.objectContaining({ pathname: '/api/tagged-contacts', search: '?tags=EDITEUR%2CEMAIL' }),
    )
    await userEvent.click(within(dialog).getByRole('button', { name: 'Ajouter 1 contact' }))

    await waitFor(() =>
      expect(calls).toContainEqual(
        expect.objectContaining({
          method: 'POST',
          pathname: `/api/campaigns/${CAMPAIGN}/recipients/by-tags`,
          body: { tags: ['#EDITEUR'] },
        }),
      ),
    )
  })

  it('are previewed as a bound while the search spans more slices than those read', async () => {
    theApiAnswers(frozen, {
      ...twoContacts,
      found: 30,
      _links: { next: { href: `/api/tagged-contacts?tags=EDITEUR,EMAIL&after=${ALAN}`, method: 'GET' } },
    })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await userEvent.click(await screen.findByRole('button', { name: 'Ajouter' }))
    const dialog = await screen.findByRole('dialog', { name: 'Ajouter des destinataires' })
    await userEvent.click(within(dialog).getByRole('button', { name: 'Filtrer par tag' }))
    await userEvent.click(await screen.findByRole('option', { name: '#EDITEUR' }))

    expect(await within(dialog).findByText('Au plus 29 contacts seront ajoutés')).toBeInTheDocument()
    expect(within(dialog).getByRole('button', { name: 'Ajouter ces contacts' })).toBeInTheDocument()
  })

  it('gain a contact chosen by their name', async () => {
    const calls = theApiAnswers(frozen)
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await userEvent.click(await screen.findByRole('button', { name: 'Ajouter' }))
    const dialog = await screen.findByRole('dialog', { name: 'Ajouter des destinataires' })
    await userEvent.click(within(dialog).getByRole('tab', { name: 'Un contact' }))
    await userEvent.type(within(dialog).getByLabelText('Rechercher un contact par son nom'), 'Alan')
    await userEvent.click(await within(dialog).findByRole('button', { name: 'Ajouter Alan Turing' }))

    await waitFor(() =>
      expect(calls).toContainEqual(
        expect.objectContaining({
          method: 'POST',
          pathname: `/api/campaigns/${CAMPAIGN}/recipients`,
          body: { contactId: ALAN },
        }),
      ),
    )
  })
})
