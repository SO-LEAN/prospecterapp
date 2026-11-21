import { MutationCache, QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { queryClient as theAppQueryClient } from '@/app/query-client'
import { Toaster } from '@/shared/ui/sonner'
import { renderInARouter } from '@/test/render-in-a-router'
import { startOfDay } from '../../calendar/dates'
import { UNDO_DELAY_MS } from '../../mutations'
import type { ExchangeDetail } from '../../queries'
import { type DayExchange, type MyDay, myDayQuery } from '../queries'
import { MyDayCards } from './my-day-cards'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

/** The toaster follows the theme of the system, which jsdom does not tell. */
vi.hoisted(() => {
  window.matchMedia = (query: string) =>
    ({
      matches: false,
      media: query,
      addEventListener: () => undefined,
      removeEventListener: () => undefined,
    }) as unknown as MediaQueryList
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

/** Friday 13 November 2026, 10:00 in the time of the browser. */
const NOW = new Date(2026, 10, 13, 10, 0)
const ADA = '0190a1c0-0000-7000-8000-00000000a000'
const ACME = '0190a1c0-0000-7000-8000-00000000ac00'
const DAY_SELF = { href: '/api/me/day', method: 'GET' }
const searchContacts = { href: '/api/contacts', method: 'GET' }

let lastId = 0
const nextId = () => `0190a1c0-0000-7000-8000-${String(++lastId).padStart(12, '0')}`

const linksOf = (id: string, toHandle: boolean) => ({
  self: { href: `/api/exchanges/${id}`, method: 'GET' },
  ...(toHandle && {
    markDone: { href: `/api/exchanges/${id}/done`, method: 'POST' },
    markMissed: { href: `/api/exchanges/${id}/missed`, method: 'POST' },
  }),
})

/** An exchange planned with Ada Lovelace of ACME, to handle once its time has come, as the API tells it. */
function planned(
  channel: DayExchange['exchange']['channel'],
  occurredAt: Date,
  exchange: Partial<DayExchange['exchange']> = {},
): DayExchange {
  const id = nextId()
  return {
    exchange: {
      id,
      channel,
      direction: 'outbound',
      occurredAt: occurredAt.toISOString(),
      status: 'planned',
      durationInMinutes: 30,
      textFlagged: false,
      textToMakeConform: false,
      toMakeConform: false,
      organizationIds: [ACME],
      attachmentCount: 0,
      contactToAttach: false,
      bookedByLink: false,
      createdByAnAI: false,
      _links: linksOf(id, occurredAt <= NOW),
      ...exchange,
    },
    interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
    organization: {
      id: ACME,
      name: 'ACME',
      organization: { href: `/api/organizations/${ACME}`, method: 'GET' },
    },
  }
}

const theDetail = (item: DayExchange, detail: Partial<ExchangeDetail> = {}): ExchangeDetail => ({
  ...item.exchange,
  interlocutor: item.interlocutor,
  canceller: null,
  linkAwaited: false,
  text: null,
  noted: { at: NOW.toISOString(), by: { name: { firstName: 'Michel', lastName: 'Maier' } } },
  corrected: null,
  madeConform: null,
  attachments: [],
  hiddenAttachments: 0,
  ...detail,
  _links: { ...item.exchange._links, interlocutor: { href: `/api/contacts/${ADA}`, method: 'GET' } },
})

const aDayOf = (today: DayExchange[], next: DayExchange[] = [], links = { searchContacts }): MyDay => ({
  today,
  next,
  _links: { self: DAY_SELF, ...links },
})

/** The detail of each exchange, by its path; my day read again, empty; an empty timeline for any other reading. */
function theApiAnswers(details: Record<string, ExchangeDetail> = {}) {
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const { pathname } = new URL(request.url)
    if (pathname === DAY_SELF.href) return Response.json(aDayOf([]))
    return Response.json(details[pathname] ?? { items: [], _links: {} })
  })
}

const sent = (method: string) =>
  theApi.mock.calls
    .map(([request]) => request as Request)
    .filter((request) => request.method === method)
    .map((request) => new URL(request.url).pathname)

async function showMyDay(myDay: MyDay) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY, retry: false } },
    mutationCache: new MutationCache(theAppQueryClient.getMutationCache().config),
  })
  queryClient.setQueryData(myDayQuery(startOfDay(NOW)).queryKey, myDay)
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <MyDayCards contactSummary={(id) => <p>{`Résumé du contact ${id}`}</p>} />
      <Toaster />
    </QueryClientProvider>,
  )
}

const theCard = (title: string) => screen.getByText(title).closest('[data-slot="card"]') as HTMLElement

beforeEach(() => {
  vi.useFakeTimers({ toFake: ['Date'] })
  vi.setSystemTime(NOW)
  theApiAnswers()
})

afterEach(() => {
  vi.useRealTimers()
  vi.restoreAllMocks()
  theApi.mockReset()
})

describe('my day on the dashboard', () => {
  it('shows today’s exchanges with their hour, channel, contact and organization, and the time left', async () => {
    await showMyDay(
      aDayOf([planned('videoCall', new Date(2026, 10, 13, 10, 25), { link: 'https://meet.test/ada' })]),
    )

    const today = within(theCard('Aujourd’hui'))
    expect(today.getByRole('button', { name: /10h25\s*Visio\s*Ada Lovelace\s*ACME/ })).toBeInTheDocument()
    expect(today.getByText(/^dans 25\smin$/)).toBeInTheDocument()
    expect(today.getByRole('link', { name: 'Rejoindre' })).toHaveAttribute('href', 'https://meet.test/ada')
  })

  it('sets apart the exchange in progress', async () => {
    await showMyDay(aDayOf([planned('meeting', new Date(2026, 10, 13, 9, 45))]))

    expect(screen.getByText('En cours').closest('[data-in-progress]')).toBeInTheDocument()
    expect(screen.queryByText('À traiter')).not.toBeInTheDocument()
  })

  it('marks to handle an exchange whose hour passed, done from its report', async () => {
    const call = planned('call', new Date(2026, 10, 13, 9, 0))
    theApiAnswers({ [`/api/exchanges/${call.exchange.id}`]: theDetail(call) })
    await showMyDay(aDayOf([call]))

    expect(screen.getByText('À traiter')).toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: 'Fait' }))

    expect(await within(await screen.findByRole('dialog')).findByText('Compte rendu')).toBeInTheDocument()
  })

  it('marks missed an exchange whose hour passed, once its toast no longer lets it be taken back', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    vi.setSystemTime(NOW)
    const call = planned('call', new Date(2026, 10, 13, 9, 0))
    theApiAnswers({ [`/api/exchanges/${call.exchange.id}/missed`]: theDetail(call) })
    await showMyDay(aDayOf([call]))
    const user = userEvent.setup({ advanceTimers: (delay) => vi.advanceTimersByTime(delay) })

    await user.click(screen.getByRole('button', { name: 'Manqué' }))
    expect(await screen.findByText('Échange marqué manqué')).toBeInTheDocument()
    expect(sent('POST')).toEqual([])
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))

    await waitFor(() => expect(sent('POST')).toEqual([`/api/exchanges/${call.exchange.id}/missed`]))
  })

  it('calls the interlocutor of a call to come, their number as the detail gives it', async () => {
    const call = planned('call', new Date(2026, 10, 13, 14, 0))
    theApiAnswers({
      [`/api/exchanges/${call.exchange.id}`]: theDetail(call, { phoneNumber: '+33 3 88 11 22 33' }),
    })
    await showMyDay(aDayOf([call]))

    expect(await screen.findByRole('link', { name: 'Appeler' })).toHaveAttribute('href', 'tel:+33388112233')
  })

  it('opens an exchange in the dialog of the calendar', async () => {
    const meeting = planned('meeting', new Date(2026, 10, 13, 16, 0))
    theApiAnswers({
      [`/api/exchanges/${meeting.exchange.id}`]: theDetail(meeting, { text: 'Démonstration' }),
    })
    await showMyDay(aDayOf([meeting]))

    await userEvent.click(screen.getByRole('button', { name: /16h00/ }))

    const dialog = await screen.findByRole('dialog')
    expect(await within(dialog).findByText('Démonstration')).toBeInTheDocument()
    expect(within(dialog).getByText(`Résumé du contact ${ADA}`)).toBeInTheDocument()
  })

  it('shows the next exchanges, tomorrow then by their day, and leads to the calendar', async () => {
    await showMyDay(
      aDayOf(
        [],
        [planned('meeting', new Date(2026, 10, 14, 9, 0)), planned('call', new Date(2026, 10, 16, 14, 0))],
      ),
    )

    const upcoming = within(theCard('À venir'))
    expect(upcoming.getAllByRole('listitem').map((item) => item.textContent)).toEqual([
      expect.stringContaining('demain 9h00'),
      expect.stringContaining('lundi 14h00'),
    ])
    expect(upcoming.getByRole('link', { name: 'Voir le calendrier' })).toHaveAttribute('href', '/calendar')
  })

  it('tells a day with nothing planned, and proposes to plan to a member who may', async () => {
    await showMyDay(aDayOf([]))

    expect(screen.getByText('Rien de prévu aujourd’hui')).toBeInTheDocument()
    expect(screen.getByText('Rien de prévu les jours suivants')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Planifier' })).toHaveAttribute('href', '/calendar?view=day')
  })

  it('does not propose to plan to a member who may not', async () => {
    await showMyDay({ today: [], next: [], _links: { self: DAY_SELF } })

    expect(screen.queryByRole('link', { name: 'Planifier' })).not.toBeInTheDocument()
  })
})
