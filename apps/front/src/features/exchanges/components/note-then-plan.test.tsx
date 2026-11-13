import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { ReactNode } from 'react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import {
  type OrganizationTimeline,
  organizationTimelineQuery,
  organizationUpcomingQuery,
  type Timeline,
  timelineQuery,
  upcomingQuery,
} from '../queries'
import { ExchangesSection } from './exchanges-section'
import { OrganizationExchangesSection } from './organization-exchanges-section'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ADA = '0190a1c0-0000-7000-8000-000000000001'
const ACME = '0190a1c0-0000-7000-8000-000000000002'
const NOTED = '0190a1c0-0000-7000-8000-000000000010'

/** A timeline of nothing yet, offering to note and to plan. */
const anEmptyTimeline = (path: string): Timeline => ({
  items: [],
  _links: {
    self: { href: path, method: 'GET' },
    noteExchange: { href: path, method: 'POST' },
    planExchange: { href: path, method: 'POST' },
  },
})

/** The API notes and plans; any other reading answers nothing. */
function theApiAnswers() {
  theApi.mockImplementation(async (request) => {
    const { method, url } = request as Request
    const path = new URL(url).pathname
    return method === 'POST'
      ? Response.json(
          { ...anEmptyTimeline(path), toMakeConform: 0 },
          { status: 201, headers: { Location: `/api/exchanges/${NOTED}` } },
        )
      : Response.json({ items: [], _links: { self: { href: path, method: 'GET' } } })
  })
}

async function renderWith(card: ReactNode, queryClient: QueryClient) {
  await renderInARouter(<QueryClientProvider client={queryClient}>{card}</QueryClientProvider>)
  return userEvent.setup()
}

/** The meeting noted, then what follows planned for tomorrow at 9. */
async function noteThenPlan(user: ReturnType<typeof userEvent.setup>) {
  await user.click(screen.getByRole('button', { name: 'Noter un échange' }))
  await user.click(screen.getByLabelText('Rendez-vous'))
  await user.click(screen.getByRole('button', { name: 'Enregistrer' }))
  await user.click(await screen.findByRole('button', { name: 'Planifier la suite' }))
  await user.click(screen.getByRole('button', { name: 'Demain 9h' }))
  await user.click(screen.getByRole('button', { name: 'Enregistrer' }))
}

/** The planning the API received, after the noting. */
async function planningSent() {
  const planning = theApi.mock.calls
    .map(([request]) => request as Request)
    .find((request) => request.method === 'POST' && request.url.endsWith('/upcoming-exchanges'))
  return planning && { path: new URL(planning.url).pathname, body: JSON.parse(await planning.clone().text()) }
}

const aCache = () => new QueryClient({ defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } } })

afterEach(() => {
  theApi.mockReset()
})

describe('noting an exchange, then planning what follows', () => {
  it('plans with the contact of the card', async () => {
    theApiAnswers()
    const queryClient = aCache()
    const timeline = anEmptyTimeline(`/api/contacts/${ADA}/exchanges`)
    queryClient.setQueryData(timelineQuery(ADA).queryKey, { pages: [timeline], pageParams: [undefined] })
    queryClient.setQueryData(upcomingQuery(ADA).queryKey, { pages: [timeline], pageParams: [undefined] })
    const user = await renderWith(
      <ExchangesSection contactId={ADA} contactName="Ada Lovelace" />,
      queryClient,
    )

    await noteThenPlan(user)

    await expect.poll(planningSent).toMatchObject({
      path: `/api/contacts/${ADA}/upcoming-exchanges`,
      body: { channel: 'meeting' },
    })
  })

  it('plans with the organization of the card, the interlocutor the exchange was noted with', async () => {
    theApiAnswers()
    const queryClient = aCache()
    const timeline: OrganizationTimeline = {
      items: [],
      toMakeConform: 0,
      _links: anEmptyTimeline(`/api/organizations/${ACME}/exchanges`)._links,
    }
    queryClient.setQueryData(organizationTimelineQuery(ACME).queryKey, {
      pages: [timeline],
      pageParams: [undefined],
    })
    queryClient.setQueryData(organizationUpcomingQuery(ACME).queryKey, {
      pages: [timeline],
      pageParams: [undefined],
    })
    const user = await renderWith(
      <OrganizationExchangesSection
        organizationId={ACME}
        interlocutors={{ organization: 'ACME', contacts: [] }}
      />,
      queryClient,
    )

    await noteThenPlan(user)

    await expect.poll(planningSent).toMatchObject({
      path: `/api/organizations/${ACME}/upcoming-exchanges`,
      body: { personId: null, exchange: { channel: 'meeting' } },
    })
  })
})
