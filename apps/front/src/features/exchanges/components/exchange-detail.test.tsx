import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { type ExchangeDetail, exchangeQuery } from '../queries'
import { ExchangeDetailSheet } from './exchange-detail'

const EXCHANGE = '0190a1c0-0000-7000-8000-000000000010'
const ADA = '0190a1c0-0000-7000-8000-000000000001'
const self = { href: `/api/exchanges/${EXCHANGE}`, method: 'GET' }

const anExchange = (exchange: Partial<ExchangeDetail>): ExchangeDetail => ({
  id: EXCHANGE,
  contact: { id: ADA, name: { firstName: 'Ada', lastName: 'Lovelace' } },
  channel: 'linkedIn',
  direction: 'inbound',
  missed: false,
  occurredAt: '2026-10-15T12:30:00+00:00',
  text: 'Intéressée par une mission en mars',
  organizationIds: [],
  noted: { at: '2026-10-15T12:35:00+00:00', by: { name: { firstName: 'Michel', lastName: 'Maier' } } },
  corrected: null,
  _links: {
    self,
    contact: { href: `/api/contacts/${ADA}`, method: 'GET' },
    correctExchange: { href: self.href, method: 'PATCH' },
    eraseExchange: { href: self.href, method: 'DELETE' },
  },
  ...exchange,
})

function renderTheDetail(exchange: ExchangeDetail) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(exchangeQuery(exchange.id).queryKey, exchange)
  render(
    <QueryClientProvider client={queryClient}>
      <ExchangeDetailSheet exchangeId={exchange.id} onClose={vi.fn()} />
    </QueryClientProvider>,
  )
  return userEvent.setup()
}

describe('ExchangeDetailSheet', () => {
  it('shows who noted the exchange, and no correction while there was none', () => {
    renderTheDetail(anExchange({}))

    expect(screen.getByText('LinkedIn, Reçu')).toBeInTheDocument()
    expect(screen.getByText('Intéressée par une mission en mars')).toBeInTheDocument()
    expect(screen.getByText(/^Noté le 15 oct\. 2026, .* par Michel Maier$/)).toBeInTheDocument()
    expect(screen.queryByText(/^Modifié le/)).not.toBeInTheDocument()
  })

  it('shows who corrected it last, named by their email while their profile has no name', () => {
    renderTheDetail(
      anExchange({
        corrected: { at: '2026-10-16T08:00:00+00:00', by: { name: {}, email: 'assistant@solean-it.io' } },
      }),
    )

    expect(screen.getByText(/^Modifié le 16 oct\. 2026, .* par assistant@solean-it\.io$/)).toBeInTheDocument()
  })

  it('offers to correct the exchange with what it is', async () => {
    const user = renderTheDetail(anExchange({}))

    await user.click(screen.getByRole('button', { name: 'Corriger' }))

    expect(screen.getByLabelText('LinkedIn')).toBeChecked()
    expect(screen.getByLabelText(/Texte/)).toHaveValue('Intéressée par une mission en mars')
  })

  it('asks before erasing it', async () => {
    const user = renderTheDetail(anExchange({}))

    await user.click(screen.getByRole('button', { name: 'Supprimer' }))

    expect(await screen.findByRole('dialog', { name: 'Supprimer cet échange ?' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Supprimer l’échange' })).toBeInTheDocument()
  })

  it('offers nothing the exchange does not link to', () => {
    renderTheDetail(
      anExchange({ _links: { self, contact: { href: `/api/contacts/${ADA}`, method: 'GET' } } }),
    )

    expect(screen.queryByRole('button', { name: 'Corriger' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Supprimer' })).not.toBeInTheDocument()
  })
})
