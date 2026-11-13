import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import {
  type Exchange,
  type ExchangeDetail,
  exchangeQuery,
  type Timeline,
  timelineQuery,
  upcomingQuery,
} from '../queries'
import { ExchangesSection } from './exchanges-section'

const ADA = '0190a1c0-0000-7000-8000-000000000001'
const timelineLink = { href: `/api/contacts/${ADA}/exchanges`, method: 'GET' }
const noteLink = { href: `/api/contacts/${ADA}/exchanges`, method: 'POST' }

const EXCHANGE = '0190a1c0-0000-7000-8000-000000000010'

const anExchange = (exchange: Partial<Exchange>): Exchange => ({
  id: EXCHANGE,
  channel: 'sms',
  direction: 'inbound',
  canceller: null,
  occurredAt: '2026-10-15T12:30:00+00:00',
  status: 'done',
  durationInMinutes: null,
  text: null,
  textFlagged: false,
  textToMakeConform: false,
  toMakeConform: false,
  organizationIds: [],
  attachmentCount: 0,
  _links: { self: { href: `/api/exchanges/${exchange.id ?? EXCHANGE}`, method: 'GET' } },
  ...exchange,
})

const upcomingLink = { href: `/api/contacts/${ADA}/upcoming-exchanges`, method: 'GET' }
const nothingToCome: Timeline = { items: [], _links: { self: upcomingLink } }

async function renderTheSection(timeline: Timeline, detail?: ExchangeDetail, upcoming = nothingToCome) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(timelineQuery(ADA).queryKey, { pages: [timeline], pageParams: [undefined] })
  queryClient.setQueryData(upcomingQuery(ADA).queryKey, { pages: [upcoming], pageParams: [undefined] })
  if (detail) {
    queryClient.setQueryData(exchangeQuery(detail.id).queryKey, detail)
  }
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <ExchangesSection contactId={ADA} contactName="Ada Lovelace" />
    </QueryClientProvider>,
  )
}

describe('ExchangesSection', () => {
  it('shows each exchange by its channel, its direction and the beginning of its text, the most recent first', async () => {
    await renderTheSection({
      items: [
        anExchange({
          id: '0190a1c0-0000-7000-8000-000000000012',
          channel: 'linkedIn',
          text: 'Intéressée par une mission en mars',
        }),
        anExchange({
          id: '0190a1c0-0000-7000-8000-000000000011',
          channel: 'call',
          direction: 'outbound',
          status: 'missed',
        }),
      ],
      _links: { self: timelineLink, noteExchange: noteLink },
    })

    const [linkedIn, call] = screen.getAllByRole('listitem')
    expect(linkedIn).toHaveTextContent('LinkedIn, Reçu')
    expect(linkedIn).toHaveTextContent('Intéressée par une mission en mars')
    expect(within(call as HTMLElement).getByText('Appel, Manqué')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Noter un échange' })).toBeInTheDocument()
  })

  it('shows what is to come above the past, and offers to plan with the link', async () => {
    const planned = anExchange({
      id: '0190a1c0-0000-7000-8000-000000000020',
      channel: 'videoCall',
      direction: 'outbound',
      occurredAt: '2099-11-17T13:00:00+00:00',
      status: 'planned',
      durationInMinutes: 60,
    })
    await renderTheSection(
      {
        items: [anExchange({ channel: 'call' })],
        _links: { self: timelineLink, planExchange: { href: upcomingLink.href, method: 'POST' } },
      },
      undefined,
      {
        items: [planned],
        _links: { self: upcomingLink, next: { href: `${upcomingLink.href}?after=x`, method: 'GET' } },
      },
    )

    const upcoming = screen.getByRole('list', { name: 'À venir' })
    expect(within(upcoming).getByText('Visio, À venir')).toBeInTheDocument()
    expect(within(upcoming).getByText(/, 1 h$/)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Charger plus' })).toBeInTheDocument()
    expect(
      within(screen.getByRole('list', { name: 'Échanges' })).getByText('Appel, Reçu'),
    ).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Planifier' })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Noter un échange' })).not.toBeInTheDocument()
  })

  it('shows how many files are joined to an exchange', async () => {
    await renderTheSection({
      items: [anExchange({ attachmentCount: 2 })],
      _links: { self: timelineLink, noteExchange: noteLink },
    })

    expect(screen.getByText('2 pièces jointes')).toBeInTheDocument()
  })

  it('flags a text that may hold personal data the reader may not manage, and offers no quick entry', async () => {
    await renderTheSection({
      items: [anExchange({ text: 'Grace rappellera lundi', textFlagged: true })],
      _links: { self: timelineLink },
    })

    const [line] = screen.getAllByRole('listitem')
    expect(line).toHaveTextContent('Peut contenir des données personnelles')
    expect(line).toHaveTextContent('Grace rappellera lundi')
    expect(screen.queryByRole('button', { name: 'Noter un échange' })).not.toBeInTheDocument()
  })

  it('says there is none yet', async () => {
    await renderTheSection({ items: [], _links: { self: timelineLink, noteExchange: noteLink } })

    expect(screen.getByText('Aucun échange noté.')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Charger plus' })).not.toBeInTheDocument()
  })

  it('offers what follows when there is more', async () => {
    await renderTheSection({
      items: [anExchange({})],
      _links: {
        self: timelineLink,
        noteExchange: noteLink,
        next: { href: `${timelineLink.href}?after=x`, method: 'GET' },
      },
    })

    expect(screen.getByRole('button', { name: 'Charger plus' })).toBeInTheDocument()
  })

  it('opens the whole exchange beside the card when a line is clicked', async () => {
    const text = 'Intéressée par une mission en mars,\nà recontacter après le salon de novembre'
    await renderTheSection(
      { items: [anExchange({ text })], _links: { self: timelineLink } },
      {
        id: EXCHANGE,
        interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
        channel: 'sms',
        direction: 'inbound',
        canceller: null,
        occurredAt: '2026-10-15T12:30:00+00:00',
        status: 'done',
        durationInMinutes: null,
        text,
        textFlagged: false,
        textToMakeConform: false,
        toMakeConform: false,
        organizationIds: [],
        noted: {
          at: '2026-10-15T12:35:00+00:00',
          by: { name: { firstName: 'Michel' }, email: 'michel@solean-it.io' },
        },
        corrected: null,
        madeConform: null,
        attachments: [],
        hiddenAttachments: 0,
        _links: { self: { href: `/api/exchanges/${EXCHANGE}`, method: 'GET' }, interlocutor: timelineLink },
      },
    )

    await userEvent.setup().click(screen.getByRole('button', { name: /SMS, Reçu/ }))

    const panel = await screen.findByRole('dialog', { name: 'Échange' })
    expect(within(panel).getByText('Ada Lovelace')).toBeInTheDocument()
    expect(within(panel).getByText(/à recontacter après le salon de novembre/)).toBeInTheDocument()
    expect(within(panel).getByText(/^Noté le .* par Michel$/)).toBeInTheDocument()
  })
})
