import { act, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { toast } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Campaign } from '@/features/campaigns/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** A campaign's daily cap. The member sets this guard on the campaign's sheet. While the campaign runs, the page shows the recipients processed today. */

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

const me: Account = {
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' }, campaigns: { href: '/api/campaigns', method: 'GET' } },
}

const started: Campaign = {
  id: CAMPAIGN,
  name: 'Relance PHP',
  channel: 'linkedIn',
  tags: ['#PHP'],
  targetedTags: ['#PHP', '#LINKEDIN'],
  message: 'Bonjour {firstName}',
  subject: null,
  messageMode: 'asIs',
  status: 'started',
  author: { name: { firstName: 'Alan', lastName: 'Turing' }, email: null },
  progress: { processed: 12, dismissed: 0 },
  targeted: 40,
  startedAt: '2026-11-16T17:00:00+00:00',
  stoppedAt: null,
  endedAt: null,
  dailyCap: 30,
  processedToday: 12,
  _links: {
    self: { href: `/api/campaigns/${CAMPAIGN}`, method: 'GET' },
    stop: { href: `/api/campaigns/${CAMPAIGN}/stop`, method: 'POST' },
    setDailyCap: { href: `/api/campaigns/${CAMPAIGN}/daily-cap`, method: 'PATCH' },
    recipients: { href: `/api/campaigns/${CAMPAIGN}/recipients`, method: 'GET' },
  },
}

type Call = { method: string; url: URL; body: unknown }

function theApiAnswers(campaign: Campaign = started) {
  const calls: Call[] = []
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const url = new URL(request.url)
    const body = request.method === 'GET' ? null : await request.text()
    calls.push({ method: request.method, url, body: body ? JSON.parse(body) : null })
    if (request.method === 'PATCH') {
      return Response.json(campaign)
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
    }
    return Response.json(answers[url.pathname] ?? { items: [], _links: {} })
  })
  return calls
}

describe('the daily cap of a campaign', () => {
  afterEach(() => {
    act(() => toast.dismiss())
    theApi.mockReset()
  })

  it('shows the recipients processed today out of the cap while the campaign is led', async () => {
    theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    expect(await screen.findByText('12 / 30 aujourd’hui')).toBeInTheDocument()
  })

  it('counts the recipients processed today on the reader’s clock', async () => {
    const calls = theApiAnswers()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await screen.findByText('12 / 30 aujourd’hui')
    const read = calls.find(
      ({ method, url }) => method === 'GET' && url.pathname === `/api/campaigns/${CAMPAIGN}`,
    )
    expect(read?.url.searchParams.get('timeZone')).toBe(Intl.DateTimeFormat().resolvedOptions().timeZone)
  })

  it('sets the cap on the sheet of the campaign', async () => {
    const calls = theApiAnswers()
    const user = userEvent.setup()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    const field = await screen.findByLabelText('Plafond par jour')
    await user.clear(field)
    await user.type(field, '20')
    await user.click(screen.getByRole('button', { name: 'Enregistrer le plafond' }))

    await waitFor(() =>
      expect(calls).toContainEqual(expect.objectContaining({ method: 'PATCH', body: { dailyCap: 20 } })),
    )
  })

  it('takes the cap off when the field is left empty', async () => {
    const calls = theApiAnswers()
    const user = userEvent.setup()
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    await user.clear(await screen.findByLabelText('Plafond par jour'))
    await user.click(screen.getByRole('button', { name: 'Enregistrer le plafond' }))

    await waitFor(() =>
      expect(calls).toContainEqual(expect.objectContaining({ method: 'PATCH', body: { dailyCap: null } })),
    )
  })

  it('no longer offers to change the cap of a stopped campaign', async () => {
    const { setDailyCap: _, ...links } = started._links
    theApiAnswers({ ...started, status: 'stopped', _links: links })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    expect((await screen.findAllByRole('heading', { name: 'Relance PHP' })).length).toBeGreaterThan(0)
    expect(screen.queryByLabelText('Plafond par jour')).not.toBeInTheDocument()
  })

  it('shows the count in orange beyond the cap', async () => {
    theApiAnswers({ ...started, processedToday: 31 })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    expect(await screen.findByText('31 / 30 aujourd’hui')).toHaveClass('text-warning')
  })

  it('shows the count as any other figure at the cap', async () => {
    theApiAnswers({ ...started, processedToday: 30 })
    await renderTheSpaAt(`/campaigns/${CAMPAIGN}`)

    expect(await screen.findByText('30 / 30 aujourd’hui')).not.toHaveClass('text-warning')
  })
})
