import { screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Campaign, CampaignList, CampaignMessage, TargetedContact } from '@/features/campaigns/queries'
import type { SuggestedTags } from '@/features/tags/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** The campaigns of the team, on the routes of the SPA (ADR 0144): the list, a draft prepared, revised and discarded. */

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
const GRACE = '0190a1c0-0000-7000-8000-000000000002'

const meAllowedToRunCampaigns: Account = {
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' }, campaigns: { href: '/api/campaigns', method: 'GET' } },
}

const relance: Campaign = {
  id: CAMPAIGN,
  name: 'Relance ESN',
  channel: 'email',
  tags: ['#ESN'],
  targetedTags: ['#ESN', '#EMAIL'],
  message: 'Bonjour {prénom}',
  subject: null,
  status: 'draft',
  author: { name: { firstName: 'Alan', lastName: 'Turing' }, email: null },
  progress: { processed: 0, dismissed: 0 },
  targeted: 2,
  startedAt: null,
  stoppedAt: null,
  endedAt: null,
  _links: {
    self: { href: `/api/campaigns/${CAMPAIGN}`, method: 'GET' },
    revise: { href: `/api/campaigns/${CAMPAIGN}`, method: 'PATCH' },
    discard: { href: `/api/campaigns/${CAMPAIGN}`, method: 'DELETE' },
    target: { href: '/api/tagged-contacts?tags=ESN,EMAIL', method: 'GET' },
  },
}

const started: Campaign = {
  ...relance,
  id: '0190a1c0-0000-7000-8000-00000000ca01',
  name: 'Appels PHP',
  status: 'started',
  startedAt: '2026-11-16T17:00:00+00:00',
  _links: { self: { href: '/api/campaigns/0190a1c0-0000-7000-8000-00000000ca01', method: 'GET' } },
}

const stopped: Campaign = {
  ...started,
  id: '0190a1c0-0000-7000-8000-00000000ca02',
  name: 'Salon 2026',
  status: 'stopped',
  stoppedAt: '2026-11-17T09:00:00+00:00',
  _links: { self: { href: '/api/campaigns/0190a1c0-0000-7000-8000-00000000ca02', method: 'GET' } },
}

const ended: Campaign = {
  ...started,
  id: '0190a1c0-0000-7000-8000-00000000ca03',
  name: 'Appels Java',
  status: 'ended',
  progress: { processed: 1, dismissed: 1 },
  targeted: 2,
  endedAt: '2026-11-17T10:00:00+00:00',
  _links: { self: { href: '/api/campaigns/0190a1c0-0000-7000-8000-00000000ca03', method: 'GET' } },
}

const list: CampaignList = {
  items: [relance],
  _links: {
    self: { href: '/api/campaigns', method: 'GET' },
    prepare: { href: '/api/campaigns', method: 'POST' },
  },
}

const aContact = (id: string, firstName: string): TargetedContact => ({
  id,
  name: { firstName, lastName: 'de ACME' },
  civility: 'x',
  jobTitle: null,
  contactDetails: { email: `${firstName}@acme.test`, phoneNumber: null, language: null },
  masked: false,
  organizations: [
    {
      id: '0190a1c0-0000-7000-8000-00000000ac00',
      name: 'ACME',
      organization: { href: '/api/organizations/0190a1c0-0000-7000-8000-00000000ac00', method: 'GET' },
    },
  ],
  tags: ['#ESN', '#EMAIL'],
  contact: { href: `/api/contacts/${id}`, method: 'GET' },
})

const messageFor = (firstName: string): CampaignMessage => ({ subject: null, text: `Bonjour ${firstName}` })

const suggested: SuggestedTags = {
  families: [
    { family: 'organizationType', tags: ['#ESN'] },
    { family: 'relationship', tags: [] },
    { family: 'technology', tags: [] },
  ],
  own: [],
  automatic: ['#EMAIL', '#PHONE', '#SMS', '#LINKEDIN'],
}

type Call = { method: string; pathname: string; search: string; body: unknown }

function theApiAnswers({
  me = meAllowedToRunCampaigns,
  refusal,
  campaigns = list,
  campaignsDenied = false,
}: {
  me?: Account
  refusal?: unknown
  campaigns?: CampaignList
  campaignsDenied?: boolean
} = {}) {
  const calls: Call[] = []
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const { pathname, search } = new URL(request.url)
    const body = request.method === 'GET' || request.method === 'DELETE' ? null : await request.text()
    calls.push({ method: request.method, pathname, search, body: body ? JSON.parse(body) : null })
    if (refusal && request.method === 'POST' && pathname === '/api/campaigns') {
      return Response.json(refusal, { status: 422, headers: { 'Content-Type': 'application/problem+json' } })
    }
    if (campaignsDenied && pathname === '/api/campaigns') {
      return Response.json(
        { title: 'Forbidden', status: 403, detail: 'Your role does not allow this action.' },
        { status: 403, headers: { 'Content-Type': 'application/problem+json' } },
      )
    }
    if (request.method === 'DELETE') {
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
      '/api/me/exchanges-to-process': { items: [], toDos: [], _links: {} },
      '/api/tags': suggested,
      '/api/campaigns': request.method === 'POST' ? relance : campaigns,
      [`/api/campaigns/${CAMPAIGN}`]: relance,
      '/api/tagged-contacts': {
        items: [aContact(ADA, 'Ada'), aContact(GRACE, 'Grace')],
        found: 2,
        _links: {},
      },
      [`/api/campaigns/${CAMPAIGN}/messages/${ADA}`]: messageFor('Ada'),
      [`/api/campaigns/${CAMPAIGN}/messages/${GRACE}`]: messageFor('Grace'),
    }
    return Response.json(answers[pathname] ?? { items: [], _links: {} })
  })
  return calls
}

describe('the campaigns of the team', () => {
  afterEach(() => theApi.mockReset())

  it('are reached from the main navigation only when my account links them', async () => {
    theApiAnswers()
    await renderTheSpaAt('/')
    const navigation = await screen.findByRole('navigation', { name: 'Navigation principale' })
    expect(await within(navigation).findByRole('link', { name: 'Campagnes' })).toBeInTheDocument()
  })

  it('are not offered without the link of my account', async () => {
    theApiAnswers({
      me: { ...meAllowedToRunCampaigns, _links: { self: meAllowedToRunCampaigns._links.self } },
    })
    await renderTheSpaAt('/')
    const navigation = await screen.findByRole('navigation', { name: 'Navigation principale' })
    await within(navigation).findByRole('link', { name: 'Contacts' })
    expect(within(navigation).queryByRole('link', { name: 'Campagnes' })).not.toBeInTheDocument()
  })

  it('say my role does not allow opening them when reached by their address, without offering to retry', async () => {
    theApiAnswers({
      me: { ...meAllowedToRunCampaigns, _links: { self: meAllowedToRunCampaigns._links.self } },
      campaignsDenied: true,
    })
    await renderTheSpaAt('/campaigns')

    expect(
      await screen.findByRole('heading', { name: 'Votre rôle ne vous permet pas d’ouvrir cette page.' }),
    ).toBeInTheDocument()
    expect(screen.queryByText('Your role does not allow this action.')).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Réessayer' })).not.toBeInTheDocument()
  })

  it('are listed with their status, their channel, their author and how far they went', async () => {
    theApiAnswers()
    await renderTheSpaAt('/campaigns')

    const item = (await screen.findByRole('link', { name: 'Relance ESN' })).closest('li') as HTMLElement
    expect(within(item).getByText('Brouillon')).toBeInTheDocument()
    expect(within(item).getByText('E-mail')).toBeInTheDocument()
    expect(within(item).getByText('par Alan Turing')).toBeInTheDocument()
    expect(within(item).getByText('0 / 2')).toBeInTheDocument()
  })

  it('are listed while they run by default, drafts, frozen and started, the stopped or ended ones when the member filters them', async () => {
    theApiAnswers({ campaigns: { ...list, items: [relance, started, stopped, ended] } })
    await renderTheSpaAt('/campaigns')

    const running = (await screen.findByRole('link', { name: 'Appels PHP' })).closest('li') as HTMLElement
    expect(within(running).getByText('Démarrée')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Relance ESN' })).toBeInTheDocument()
    expect(screen.queryByRole('link', { name: 'Salon 2026' })).not.toBeInTheDocument()

    await userEvent.click(screen.getByRole('tab', { name: 'Arrêtées ou terminées' }))

    const kept = (await screen.findByRole('link', { name: 'Salon 2026' })).closest('li') as HTMLElement
    expect(within(kept).getByText('Arrêtée')).toBeInTheDocument()
    const over = screen.getByRole('link', { name: 'Appels Java' }).closest('li') as HTMLElement
    expect(within(over).getByText('Terminée')).toBeInTheDocument()
    expect(within(over).getByTitle('2 traités sur 2 ciblés')).toHaveTextContent('2 / 2')
    expect(screen.queryByRole('link', { name: 'Relance ESN' })).not.toBeInTheDocument()

    await userEvent.click(screen.getByRole('tab', { name: 'Toutes' }))

    expect(await screen.findByRole('link', { name: 'Relance ESN' })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Salon 2026' })).toBeInTheDocument()
  })

  it('says none is stopped nor ended while the filter finds none', async () => {
    theApiAnswers()
    await renderTheSpaAt('/campaigns?status=stopped')

    expect(await screen.findByText('Aucune campagne arrêtée ni terminée.')).toBeInTheDocument()
  })

  it('prepares a draft from the tags chosen and opens it, the subject sent for an e-mail only', async () => {
    const calls = theApiAnswers()
    await renderTheSpaAt('/campaigns/new')

    await userEvent.type(await screen.findByLabelText('Nom'), 'Relance ESN')
    await userEvent.click(screen.getByRole('button', { name: 'Filtrer par tag' }))
    await userEvent.click(await screen.findByRole('option', { name: '#ESN' }))
    await userEvent.type(screen.getByLabelText(/Objet/), 'Une question')
    await userEvent.type(screen.getByLabelText('Message type'), 'Bonjour')
    await userEvent.click(screen.getByRole('button', { name: 'Préparer la campagne' }))

    await waitFor(() =>
      expect(calls).toContainEqual(
        expect.objectContaining({
          method: 'POST',
          pathname: '/api/campaigns',
          body: {
            name: 'Relance ESN',
            channel: 'email',
            tags: ['#ESN'],
            message: 'Bonjour',
            subject: 'Une question',
          },
        }),
      ),
    )
    expect(await screen.findByRole('heading', { name: 'Relance ESN' })).toBeInTheDocument()
  })

  it('names the variables of the message, the civility first', async () => {
    theApiAnswers()
    await renderTheSpaAt('/campaigns/new')

    expect(
      await screen.findByText(/\{civilité\}, \{prénom\}, \{nom\}, \{organisation\}, \{fonction\}/),
    ).toBeInTheDocument()
  })

  it('shows the contacts targeted as the tags and the channel of a new campaign are chosen, before preparing it', async () => {
    const calls = theApiAnswers()
    await renderTheSpaAt('/campaigns/new')

    expect(
      await screen.findByText('Choisissez au moins un tag pour voir les contacts ciblés.'),
    ).toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: 'Filtrer par tag' }))
    await userEvent.click(await screen.findByRole('option', { name: '#ESN' }))
    expect(await screen.findByText('2 contacts ciblés')).toBeInTheDocument()
    expect(calls).toContainEqual(
      expect.objectContaining({ pathname: '/api/tagged-contacts', search: '?tags=ESN%2CEMAIL' }),
    )

    await userEvent.click(screen.getByLabelText('Canal'))
    await userEvent.click(await screen.findByRole('option', { name: 'Appel' }))

    await waitFor(() =>
      expect(calls).toContainEqual(
        expect.objectContaining({ pathname: '/api/tagged-contacts', search: '?tags=ESN%2CPHONE' }),
      ),
    )
    expect(calls.filter((call) => call.method === 'POST')).toEqual([])
  })

  it('tells a refusal of the api on its field', async () => {
    theApiAnswers({
      refusal: {
        title: 'Refused',
        status: 422,
        reason: 'invalidTagSelection',
        violations: [
          { propertyPath: 'tags', title: 'Choose from 1 to 5 tags', reason: 'invalidTagSelection' },
        ],
      },
    })
    await renderTheSpaAt('/campaigns/new')

    await userEvent.type(await screen.findByLabelText('Nom'), 'Relance ESN')
    await userEvent.click(screen.getByRole('button', { name: 'Filtrer par tag' }))
    await userEvent.click(await screen.findByRole('option', { name: '#ESN' }))
    await userEvent.click(screen.getByRole('button', { name: 'Préparer la campagne' }))

    expect(await screen.findByText('Choisissez de 1 à 5 tags.')).toBeInTheDocument()
  })

  it('shows the contacts the draft targets and the message saved rendered by the api for the contact hovered', async () => {
    const calls = theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    expect(await screen.findByText('2 contacts ciblés')).toBeInTheDocument()
    expect(calls).toContainEqual(
      expect.objectContaining({ pathname: '/api/tagged-contacts', search: '?tags=ESN%2CEMAIL' }),
    )
    expect(await screen.findByText('Bonjour Ada')).toBeInTheDocument()
    await userEvent.hover(screen.getByRole('button', { name: /Grace de ACME/ }))
    expect(await screen.findByText('Bonjour Grace')).toBeInTheDocument()
  })

  it('says the preview shows the message saved while the form differs from it', async () => {
    theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await userEvent.type(await screen.findByLabelText('Message type'), ', ça va ?')

    expect(
      await screen.findByText(
        'Aperçu du message enregistré : enregistrez vos modifications pour le mettre à jour.',
      ),
    ).toBeInTheDocument()
  })

  it('discards a draft from its menu and goes back to the list', async () => {
    const calls = theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await userEvent.click(await screen.findByRole('button', { name: /^Actions sur «\s*Relance ESN\s*»$/ }))
    await userEvent.click(await screen.findByRole('menuitem', { name: 'Supprimer' }))

    await waitFor(() =>
      expect(calls).toContainEqual(
        expect.objectContaining({ method: 'DELETE', pathname: `/api/campaigns/${CAMPAIGN}` }),
      ),
    )
    expect(await screen.findByRole('heading', { name: 'Campagnes' })).toBeInTheDocument()
  })
})
