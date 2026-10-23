import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import { type ExchangeDetail, exchangeQuery } from '../queries'
import { ExchangeDetailSheet } from './exchange-detail'

const EXCHANGE = '0190a1c0-0000-7000-8000-000000000010'
const ADA = '0190a1c0-0000-7000-8000-000000000001'
const ACME = '0190a1c0-0000-7000-8000-000000000002'
const self = { href: `/api/exchanges/${EXCHANGE}`, method: 'GET' }

const anExchange = (exchange: Partial<ExchangeDetail>): ExchangeDetail => ({
  id: EXCHANGE,
  interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
  channel: 'linkedIn',
  direction: 'inbound',
  missed: false,
  occurredAt: '2026-10-15T12:30:00+00:00',
  text: 'Intéressée par une mission en mars',
  textFlagged: false,
  textToMakeConform: false,
  toMakeConform: false,
  organizationIds: [],
  noted: { at: '2026-10-15T12:35:00+00:00', by: { name: { firstName: 'Michel', lastName: 'Maier' } } },
  corrected: null,
  madeConform: null,
  attachments: [],
  hiddenAttachments: 0,
  _links: {
    self,
    interlocutor: { href: `/api/contacts/${ADA}`, method: 'GET' },
    correctExchange: { href: `${self.href}/facts`, method: 'PATCH' },
    correctText: { href: `${self.href}/text`, method: 'PATCH' },
    eraseExchange: { href: self.href, method: 'DELETE' },
  },
  ...exchange,
})

async function renderTheDetail(exchange: ExchangeDetail) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(exchangeQuery(exchange.id).queryKey, exchange)
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <ExchangeDetailSheet exchangeId={exchange.id} onClose={vi.fn()} />
    </QueryClientProvider>,
  )
  return userEvent.setup()
}

describe('ExchangeDetailSheet', () => {
  it('shows who it was with, who noted it, and no correction while there was none', async () => {
    await renderTheDetail(anExchange({}))

    expect(screen.getByText('Ada Lovelace')).toBeInTheDocument()
    expect(screen.getByText('LinkedIn, Reçu')).toBeInTheDocument()
    expect(screen.getByText('Intéressée par une mission en mars')).toBeInTheDocument()
    expect(screen.getByText(/^Noté le 15 oct\. 2026, .* par Michel Maier$/)).toBeInTheDocument()
    expect(screen.queryByText(/^Modifié le/)).not.toBeInTheDocument()
    expect(screen.queryByText('Peut contenir des données personnelles')).not.toBeInTheDocument()
  })

  it('flags a text that may hold personal data the reader may not manage', async () => {
    await renderTheDetail(anExchange({ textFlagged: true }))

    expect(screen.getByText('Peut contenir des données personnelles')).toBeInTheDocument()
    expect(screen.getByText('Intéressée par une mission en mars')).toBeInTheDocument()
  })

  it('leads to the card of the contact, its name struck through once it left the organization', async () => {
    await renderTheDetail(
      anExchange({ interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'removed' } }),
    )

    const card = screen.getByRole('link', { name: 'Ada Lovelace' })
    expect(card).toHaveAttribute('href', `/contacts/${ADA}`)
    expect(card).toHaveClass('line-through')
  })

  it('names the organization itself when it was with no contact', async () => {
    await renderTheDetail(
      anExchange({ interlocutor: { kind: 'organization', id: ACME, name: 'ACME', presence: null } }),
    )

    expect(screen.getByText('Organisation :')).toBeInTheDocument()
    expect(screen.getByText('ACME')).toBeInTheDocument()
  })

  it('shows who corrected it last, named by their email while their profile has no name', async () => {
    await renderTheDetail(
      anExchange({
        corrected: { at: '2026-10-16T08:00:00+00:00', by: { name: {}, email: 'assistant@solean-it.io' } },
      }),
    )

    expect(screen.getByText(/^Modifié le 16 oct\. 2026, .* par assistant@solean-it\.io$/)).toBeInTheDocument()
  })

  it('offers to correct the exchange with what it is', async () => {
    const user = await renderTheDetail(anExchange({}))

    await user.click(screen.getByRole('button', { name: 'Corriger' }))

    expect(screen.getByLabelText('LinkedIn')).toBeChecked()
    expect(screen.getByLabelText(/Texte/)).toHaveValue('Intéressée par une mission en mars')
  })

  it('offers the whole correction rather than the text alone when it may do both', async () => {
    await renderTheDetail(anExchange({}))

    expect(screen.queryByRole('button', { name: 'Corriger le texte' })).not.toBeInTheDocument()
  })

  it('offers to correct its text alone when that is all it links to', async () => {
    const user = await renderTheDetail(
      anExchange({
        textFlagged: true,
        _links: {
          self,
          interlocutor: { href: `/api/contacts/${ADA}`, method: 'GET' },
          correctText: { href: `${self.href}/text`, method: 'PATCH' },
        },
      }),
    )

    expect(screen.queryByRole('button', { name: 'Corriger' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Supprimer' })).not.toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Corriger le texte' }))

    expect(screen.getByLabelText(/Texte/)).toHaveValue('Intéressée par une mission en mars')
    expect(screen.queryByRole('group', { name: 'Canal' })).not.toBeInTheDocument()
  })

  it('offers the correction no case to declare it made conform while its contact is known', async () => {
    const user = await renderTheDetail(anExchange({}))

    await user.click(screen.getByRole('button', { name: 'Corriger' }))

    expect(screen.queryByLabelText('Rendu conforme')).not.toBeInTheDocument()
  })

  it('shows what is to make conform once its contact was forgotten, and offers to declare it made conform', async () => {
    const user = await renderTheDetail(
      anExchange({
        interlocutor: { kind: 'person', id: ADA, name: null, presence: 'forgotten' },
        textToMakeConform: true,
        toMakeConform: true,
        _links: {
          self,
          correctText: { href: `${self.href}/text`, method: 'PATCH' },
          makeConform: { href: `${self.href}/text`, method: 'PATCH' },
        },
      }),
    )

    expect(screen.getByText('Contact effacé')).toBeInTheDocument()
    expect(
      screen.getByText('Ce contact a été effacé (RGPD) : retouchez ce contenu pour le rendre conforme.'),
    ).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Corriger le texte' }))

    expect(screen.getByLabelText('Rendu conforme')).not.toBeChecked()
  })

  it('shows who declared it made conform', async () => {
    await renderTheDetail(
      anExchange({
        madeConform: {
          at: '2026-10-16T08:00:00+00:00',
          by: { name: { firstName: 'Michel', lastName: 'Maier' } },
        },
      }),
    )

    expect(screen.getByText(/^Rendu conforme le 16 oct\. 2026, .* par Michel Maier$/)).toBeInTheDocument()
    expect(screen.queryByText(/retouchez ce contenu/)).not.toBeInTheDocument()
  })

  it('asks before erasing it', async () => {
    const user = await renderTheDetail(anExchange({}))

    await user.click(screen.getByRole('button', { name: 'Supprimer' }))

    expect(await screen.findByRole('dialog', { name: 'Supprimer cet échange ?' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Supprimer l’échange' })).toBeInTheDocument()
  })

  it('offers nothing the exchange does not link to', async () => {
    await renderTheDetail(
      anExchange({ _links: { self, interlocutor: { href: `/api/contacts/${ADA}`, method: 'GET' } } }),
    )

    expect(screen.queryByRole('button', { name: 'Corriger' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Corriger le texte' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Supprimer' })).not.toBeInTheDocument()
    expect(screen.queryByText('Pièces jointes')).not.toBeInTheDocument()
  })

  it('lists the files joined to it, each downloaded through the API and removed', async () => {
    const attachment = `${self.href}/attachments/0190a1c0-0000-7000-8000-000000000020`
    await renderTheDetail(
      anExchange({
        attachments: [
          {
            id: '0190a1c0-0000-7000-8000-000000000020',
            name: 'cahier-des-charges.pdf',
            size: 1572864,
            mediaType: 'application/pdf',
            toMakeConform: false,
            _links: {
              download: { href: attachment, method: 'GET' },
              removeAttachment: { href: attachment, method: 'DELETE' },
            },
          },
        ],
      }),
    )

    expect(screen.getByRole('link', { name: 'Télécharger cahier-des-charges.pdf' })).toHaveAttribute(
      'href',
      expect.stringMatching(new RegExp(`${attachment}$`)),
    )
    expect(screen.getByText('1,5 Mo')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Retirer cahier-des-charges.pdf' })).toBeInTheDocument()
  })

  it('only counts the files hidden to a reader without the right to personal data', async () => {
    await renderTheDetail(
      anExchange({
        hiddenAttachments: 2,
        _links: { self, interlocutor: { href: `/api/contacts/${ADA}`, method: 'GET' } },
      }),
    )

    expect(screen.getByText('2 pièces jointes cachées')).toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /^Télécharger/ })).not.toBeInTheDocument()
  })

  it('offers to join files while it has room for them', async () => {
    await renderTheDetail(
      anExchange({
        _links: {
          self,
          interlocutor: { href: `/api/contacts/${ADA}`, method: 'GET' },
          attachFile: { href: `${self.href}/attachments`, method: 'POST' },
        },
      }),
    )

    expect(screen.getByText('choisissez-les')).toBeInTheDocument()
    expect(screen.getByText('10 Mo au plus par fichier.')).toBeInTheDocument()
  })
})
