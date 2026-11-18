import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import type { Exchange } from '../queries'
import { ExchangeLine } from './exchange-line'

const EXCHANGE = '0190a1c0-0000-7000-8000-000000000010'
const CAMPAIGN = '0190a1c0-0000-7000-8000-00000000ca00'
const self = { href: `/api/exchanges/${EXCHANGE}`, method: 'GET' }

const anExchange = (exchange: Partial<Exchange>): Exchange => ({
  id: EXCHANGE,
  channel: 'email',
  direction: 'outbound',
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
  contactToAttach: false,
  _links: { self },
  ...exchange,
})

/** A line out of a button, as the last exchanges of the calendar show it: its campaign within it. */
describe('ExchangeLine', () => {
  it('leads to the campaign of the exchange when the api links it', async () => {
    await renderInARouter(
      <ExchangeLine
        exchange={anExchange({
          campaign: { id: CAMPAIGN, name: 'Relance' },
          _links: { self, campaign: { href: `/api/campaigns/${CAMPAIGN}`, method: 'GET' } },
        })}
      />,
    )

    expect(screen.getByRole('link', { name: 'Campagne : Relance' })).toHaveAttribute(
      'href',
      `/campaigns/${CAMPAIGN}`,
    )
  })

  it('says the campaign of the exchange was erased', async () => {
    await renderInARouter(<ExchangeLine exchange={anExchange({ campaign: { id: CAMPAIGN, name: null } })} />)

    expect(screen.getByText('Campagne effacée')).toBeInTheDocument()
  })

  it('leaves the campaign to the timeline that shows it beside the line', async () => {
    await renderInARouter(
      <ExchangeLine exchange={anExchange({ campaign: { id: CAMPAIGN, name: null } })} campaignAside />,
    )

    expect(screen.queryByText('Campagne effacée')).not.toBeInTheDocument()
  })
})
