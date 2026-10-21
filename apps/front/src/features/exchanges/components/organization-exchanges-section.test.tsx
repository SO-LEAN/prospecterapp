import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { type Exchange, type OrganizationTimeline, organizationTimelineQuery } from '../queries'
import { OrganizationExchangesSection } from './organization-exchanges-section'

const ACME = '0190a1c0-0000-7000-8000-000000000001'
const ROBERT = '0190a1c0-0000-7000-8000-000000000002'
const timelineLink = { href: `/api/organizations/${ACME}/exchanges`, method: 'GET' }
const noteLink = { href: `/api/organizations/${ACME}/exchanges`, method: 'POST' }

const anExchange = (exchange: Partial<Exchange>): Exchange => ({
  id: '0190a1c0-0000-7000-8000-000000000010',
  channel: 'call',
  direction: 'inbound',
  missed: false,
  occurredAt: '2026-10-15T14:00:00+00:00',
  text: null,
  organizationIds: [ACME],
  _links: { self: { href: '/api/exchanges/0190a1c0-0000-7000-8000-000000000010', method: 'GET' } },
  ...exchange,
})

function renderTheSection(timeline: OrganizationTimeline) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(organizationTimelineQuery(ACME).queryKey, {
    pages: [timeline],
    pageParams: [undefined],
  })
  render(
    <QueryClientProvider client={queryClient}>
      <OrganizationExchangesSection
        organizationId={ACME}
        interlocutors={{ organization: 'ACME', contacts: [{ id: ROBERT, name: 'Robert Noyce' }] }}
      />
    </QueryClientProvider>,
  )
}

describe('OrganizationExchangesSection', () => {
  it('says who communicated in each exchange: the organization itself or one of its contacts', () => {
    renderTheSection({
      items: [
        {
          exchange: anExchange({ id: '0190a1c0-0000-7000-8000-000000000012' }),
          interlocutor: { kind: 'organization', id: ACME, name: 'ACME' },
        },
        {
          exchange: anExchange({ id: '0190a1c0-0000-7000-8000-000000000011', direction: 'outbound' }),
          interlocutor: { kind: 'person', id: ROBERT, name: 'Robert Noyce' },
        },
        {
          exchange: anExchange({ id: '0190a1c0-0000-7000-8000-000000000010', channel: 'sms' }),
          interlocutor: { kind: 'person', id: '0190a1c0-0000-7000-8000-000000000003', name: null },
        },
      ],
      _links: { self: timelineLink, noteExchange: noteLink },
    })

    const [itself, robert, erased] = screen.getAllByRole('listitem')
    expect(itself).toHaveTextContent(/Organisation :\s*ACME/)
    expect(itself).toHaveTextContent('Appel, Reçu')
    expect(robert).toHaveTextContent(/Contact :\s*Robert Noyce/)
    expect(robert).toHaveTextContent('Appel, Envoyé')
    expect(erased).toHaveTextContent(/Contact :\s*Contact effacé/)
    expect(screen.getByRole('button', { name: 'Noter un échange' })).toBeInTheDocument()
  })

  it('offers no entry when the timeline does not', () => {
    renderTheSection({ items: [], _links: { self: timelineLink } })

    expect(screen.getByText('Aucun échange noté.')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Noter un échange' })).not.toBeInTheDocument()
  })
})
