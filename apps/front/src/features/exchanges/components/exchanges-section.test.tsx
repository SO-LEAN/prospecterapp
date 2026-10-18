import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, within } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { type Exchange, type Timeline, timelineQuery } from '../queries'
import { ExchangesSection } from './exchanges-section'

const ADA = '0190a1c0-0000-7000-8000-000000000001'
const timelineLink = { href: `/api/contacts/${ADA}/exchanges`, method: 'GET' }
const noteLink = { href: `/api/contacts/${ADA}/exchanges`, method: 'POST' }

const anExchange = (exchange: Partial<Exchange>): Exchange => ({
  id: '0190a1c0-0000-7000-8000-000000000010',
  channel: 'sms',
  direction: 'inbound',
  missed: false,
  occurredAt: '2026-10-15T12:30:00+00:00',
  text: null,
  organizationIds: [],
  ...exchange,
})

function renderTheSection(timeline: Timeline) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(timelineQuery(ADA).queryKey, { pages: [timeline], pageParams: [undefined] })
  render(
    <QueryClientProvider client={queryClient}>
      <ExchangesSection contactId={ADA} contactName="Ada Lovelace" />
    </QueryClientProvider>,
  )
}

describe('ExchangesSection', () => {
  it('shows each exchange by its channel, its direction and the beginning of its text, the most recent first', () => {
    renderTheSection({
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
          missed: true,
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

  it('says there is none yet', () => {
    renderTheSection({ items: [], _links: { self: timelineLink, noteExchange: noteLink } })

    expect(screen.getByText('Aucun échange noté.')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Charger plus' })).not.toBeInTheDocument()
  })

  it('offers what follows when there is more', () => {
    renderTheSection({
      items: [anExchange({})],
      _links: {
        self: timelineLink,
        noteExchange: noteLink,
        next: { href: `${timelineLink.href}?after=x`, method: 'GET' },
      },
    })

    expect(screen.getByRole('button', { name: 'Charger plus' })).toBeInTheDocument()
  })
})
