import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import type { Exchange, Interlocutor } from '../queries'
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

const PAULINE = '0190a1c0-0000-7000-8000-00000000a000'
const pauline: Interlocutor = { kind: 'person', id: PAULINE, name: 'Pauline Martin', presence: 'present' }

/** One exchange in a timeline, the same in every one. */
describe('ExchangeLine', () => {
  it('shows its full moment, its type tile, its title, who it is with and their organization', async () => {
    await renderInARouter(
      <ExchangeLine
        exchange={anExchange({
          channel: 'call',
          status: 'planned',
          occurredAt: new Date(2026, 10, 16, 14, 30).toISOString(),
        })}
        interlocutor={pauline}
        organization="Renault Group"
      />,
    )

    expect(screen.getByText('lun. 16 nov. · 14h30')).toBeInTheDocument()
    expect(screen.getByRole('img', { name: 'Appel, sortant' })).toBeInTheDocument()
    expect(screen.getByText('Appel, À venir')).toBeInTheDocument()
    expect(screen.getByText('Pauline Martin · Renault Group')).toBeInTheDocument()
  })

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

  it('carries the markers its fields give', async () => {
    await renderInARouter(
      <ExchangeLine
        exchange={anExchange({
          createdByAnAI: true,
          importedFrom: 'google',
          bookedByLink: true,
          contactToAttach: true,
          toRedact: true,
        })}
      />,
    )

    expect(screen.getByTitle('Créé par une IA')).toHaveTextContent('IA')
    expect(screen.getByRole('img', { name: 'Importé de Gmail' })).toBeInTheDocument()
    expect(screen.getByRole('img', { name: 'Réservé par lien' })).toBeInTheDocument()
    expect(screen.getByText('À rattacher')).toBeInTheDocument()
    expect(screen.getByText('À mettre en conformité')).toBeInTheDocument()
  })

  it('carries no marker its fields do not give', async () => {
    await renderInARouter(<ExchangeLine exchange={anExchange({})} />)

    expect(screen.queryByTitle('Créé par une IA')).not.toBeInTheDocument()
    expect(screen.queryByRole('img', { name: 'Importé de Gmail' })).not.toBeInTheDocument()
    expect(screen.queryByRole('img', { name: 'Réservé par lien' })).not.toBeInTheDocument()
    expect(screen.queryByText('À rattacher')).not.toBeInTheDocument()
    expect(screen.queryByText('À mettre en conformité')).not.toBeInTheDocument()
  })

  it('opens its detail by a click on the line, named by its moment, its title and who it is with', async () => {
    const onOpen = vi.fn()
    await renderInARouter(
      <ExchangeLine
        exchange={anExchange({
          channel: 'call',
          status: 'planned',
          occurredAt: new Date(2026, 10, 16, 14, 30).toISOString(),
        })}
        interlocutor={pauline}
        organization="Renault Group"
        onOpen={onOpen}
      />,
    )

    await userEvent.click(
      screen.getByRole('button', {
        name: /^lun\. 16 nov\. · 14h30 Appel, À venir Contact\s:\s+Pauline Martin · Renault Group$/,
      }),
    )

    expect(onOpen).toHaveBeenCalledOnce()
  })

  it('keeps its actions apart from what opens its detail', async () => {
    const onOpen = vi.fn()
    const act = vi.fn()
    await renderInARouter(
      <ExchangeLine
        exchange={anExchange({})}
        onOpen={onOpen}
        actions={
          <button type="button" onClick={act}>
            Agir
          </button>
        }
      />,
    )

    await userEvent.click(screen.getByRole('button', { name: 'Agir' }))

    expect(act).toHaveBeenCalledOnce()
    expect(onOpen).not.toHaveBeenCalled()
  })

  it('tells screen readers whether it is with a contact or with the organization itself', async () => {
    await renderInARouter(
      <>
        <ExchangeLine exchange={anExchange({})} interlocutor={pauline} />
        <ExchangeLine
          exchange={anExchange({})}
          interlocutor={{ kind: 'organization', id: PAULINE, name: 'ACME', presence: null }}
        />
      </>,
    )

    expect(screen.getByText('Contact :')).toHaveClass('sr-only')
    expect(screen.getByText('Organisation :')).toHaveClass('sr-only')
  })

  it('names a forgotten contact « Contact effacé »', async () => {
    await renderInARouter(
      <ExchangeLine exchange={anExchange({})} interlocutor={{ ...pauline, presence: 'forgotten' }} />,
    )

    expect(screen.getByText('Contact effacé')).toBeInTheDocument()
  })

  it('strikes through a contact who left the organization', async () => {
    await renderInARouter(
      <ExchangeLine
        exchange={anExchange({})}
        interlocutor={{ ...pauline, presence: 'removed' }}
        organization="ACME"
      />,
    )

    expect(screen.getByText('Pauline Martin · ACME')).toHaveClass('line-through')
  })

  it('strikes through a cancelled exchange, and says who cancelled it', async () => {
    await renderInARouter(
      <ExchangeLine
        exchange={anExchange({
          channel: 'call',
          status: 'cancelled',
          canceller: 'contact',
          occurredAt: new Date(2026, 10, 16, 14, 30).toISOString(),
        })}
      />,
    )

    expect(screen.getByText('lun. 16 nov. · 14h30')).toHaveClass('line-through')
    expect(screen.getByText('Appel, Annulé')).toHaveClass('line-through')
    expect(screen.getByText('Annulé par le contact')).toBeInTheDocument()
  })

  it('strikes through nothing on an exchange done, and names no one who cancelled it', async () => {
    await renderInARouter(
      <ExchangeLine
        exchange={anExchange({ channel: 'call', occurredAt: new Date(2026, 10, 16, 14, 30).toISOString() })}
      />,
    )

    expect(screen.getByText('lun. 16 nov. · 14h30')).not.toHaveClass('line-through')
    expect(screen.getByText('Appel, Émis')).not.toHaveClass('line-through')
    expect(screen.queryByText(/Annulé par/)).not.toBeInTheDocument()
  })

  it('says the campaign of the exchange was erased', async () => {
    await renderInARouter(<ExchangeLine exchange={anExchange({ campaign: { id: CAMPAIGN, name: null } })} />)

    expect(screen.getByText('Campagne effacée')).toBeInTheDocument()
  })
})
