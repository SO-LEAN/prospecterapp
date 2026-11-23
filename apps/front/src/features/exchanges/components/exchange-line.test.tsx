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
  textToRedact: false,
  toRedact: false,
  organizationIds: [],
  attachmentCount: 0,
  contactToAttach: false,
  bookedByLink: false,
  createdByAnAI: false,
  _links: { self },
  ...exchange,
})

/** A line outside a button, as the calendar's last exchanges show it. Its campaign is within it. */
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

  it('marks an exchange imported from Gmail', async () => {
    await renderInARouter(<ExchangeLine exchange={anExchange({ importedFrom: 'google' })} />)

    expect(screen.getByTitle('Importé de Gmail')).toBeInTheDocument()
  })

  it('does not mark an exchange noted', async () => {
    await renderInARouter(<ExchangeLine exchange={anExchange({ importedFrom: null })} />)

    expect(screen.queryByTitle('Importé de Gmail')).not.toBeInTheDocument()
  })

  it('badges an exchange an AI created, without naming the AI', async () => {
    await renderInARouter(<ExchangeLine exchange={anExchange({ createdByAnAI: true })} />)

    expect(screen.getByTitle('Créé par une IA')).toHaveTextContent('MCP')
  })

  it('does not badge an exchange noted by hand', async () => {
    await renderInARouter(<ExchangeLine exchange={anExchange({ createdByAnAI: false })} />)

    expect(screen.queryByTitle('Créé par une IA')).not.toBeInTheDocument()
  })
})
