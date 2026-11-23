import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import { type ExchangeDetail, exchangeQuery } from '../queries'
import { ExchangeDetailSheet } from './exchange-detail'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const EXCHANGE = '0190a1c0-0000-7000-8000-000000000010'
const ADA = '0190a1c0-0000-7000-8000-000000000001'
const ACME = '0190a1c0-0000-7000-8000-000000000002'
const CAMPAIGN = '0190a1c0-0000-7000-8000-00000000ca00'
const self = { href: `/api/exchanges/${EXCHANGE}`, method: 'GET' }

const anExchange = (exchange: Partial<ExchangeDetail>): ExchangeDetail => ({
  revision: 1,
  id: EXCHANGE,
  interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
  channel: 'linkedIn',
  direction: 'inbound',
  canceller: null,
  occurredAt: '2026-10-15T12:30:00+00:00',
  status: 'done',
  durationInMinutes: null,
  text: 'Intéressée par une mission en mars',
  place: null,
  link: null,
  linkAwaited: false,
  phoneNumber: null,
  textFlagged: false,
  textToRedact: false,
  toRedact: false,
  organizationIds: [],
  noted: { at: '2026-10-15T12:35:00+00:00', by: { name: { firstName: 'Michel', lastName: 'Maier' } } },
  corrected: null,
  redacted: null,
  attachments: [],
  hiddenAttachments: 0,
  bookedByLink: false,
  createdByAnAI: false,
  _links: {
    self,
    interlocutor: { href: `/api/contacts/${ADA}`, method: 'GET' },
    correctExchange: { href: `${self.href}/facts`, method: 'PATCH' },
    correctText: { href: `${self.href}/text`, method: 'PATCH' },
    eraseExchange: { href: self.href, method: 'DELETE' },
  },
  ...exchange,
})

async function renderTheDetail(exchange: ExchangeDetail, queryClient = aQueryClient()) {
  queryClient.setQueryData(exchangeQuery(exchange.id).queryKey, exchange)
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <ExchangeDetailSheet exchangeId={exchange.id} onClose={vi.fn()} />
    </QueryClientProvider>,
  )
  return userEvent.setup()
}

function aQueryClient() {
  return new QueryClient({ defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } } })
}

/** The revision that each correction was sent with. The API answers each one with the exchange at the next revision. */
function revisionsSent(exchange: ExchangeDetail) {
  const revisions: Array<{ path: string; revision: string | null }> = []
  theApi.mockImplementation(async (input) => {
    const url = new URL((input as Request).url)
    revisions.push({ path: url.pathname, revision: url.searchParams.get('revision') })
    return Response.json({ ...exchange, revision: exchange.revision + 1 })
  })
  return revisions
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

  it('leads to the campaign it was noted in', async () => {
    await renderTheDetail(
      anExchange({
        campaign: { id: CAMPAIGN, name: 'Relance' },
        _links: { self, campaign: { href: `/api/campaigns/${CAMPAIGN}`, method: 'GET' } },
      }),
    )

    expect(screen.getByRole('link', { name: 'Campagne\u00a0: Relance' })).toHaveAttribute(
      'href',
      `/campaigns/${CAMPAIGN}`,
    )
  })

  it('names the campaign it was noted in without leading to it when the reader may not read the campaigns', async () => {
    await renderTheDetail(anExchange({ campaign: { id: CAMPAIGN, name: 'Relance' } }))

    expect(screen.getByText('Campagne : Relance')).toBeInTheDocument()
    expect(screen.queryByRole('link', { name: 'Campagne\u00a0: Relance' })).not.toBeInTheDocument()
  })

  it('says the campaign it was noted in was erased', async () => {
    await renderTheDetail(anExchange({ campaign: { id: CAMPAIGN, name: null } }))

    expect(screen.getByText('Campagne effacée')).toBeInTheDocument()
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

  it('says an exchange its contact booked was booked by the public link, Google inviting them, nobody of the account planning it', async () => {
    await renderTheDetail(
      anExchange({ status: 'planned', channel: 'videoCall', durationInMinutes: 15, bookedByLink: true }),
    )

    expect(
      screen.getByText(
        /^Réservé le 15 oct\. 2026, .* par votre lien public · invitation envoyée par Google$/,
      ),
    ).toBeInTheDocument()
    expect(screen.queryByText(/^Planifié le/)).not.toBeInTheDocument()
  })

  it('says an exchange was imported from Gmail rather than noted', async () => {
    await renderTheDetail(anExchange({ channel: 'email', importedFrom: 'google' }))

    expect(screen.getByText(/^Importé de Gmail le 15 oct\. 2026, .*\d$/)).toBeInTheDocument()
    expect(screen.queryByText(/^Noté le/)).not.toBeInTheDocument()
  })

  it('badges an exchange an AI noted, its member its author', async () => {
    await renderTheDetail(anExchange({ createdByAnAI: true }))

    expect(screen.getByTitle('Créé par une IA')).toHaveTextContent('MCP')
    expect(screen.getByText(/^Noté le 15 oct\. 2026, .* par Michel Maier$/)).toBeInTheDocument()
  })

  it('offers to open the e-mail an exchange was imported from in Gmail, in a new tab', async () => {
    const message = 'https://mail.google.com/mail/#all/18c2f4a9e0b1d3c7'
    await renderTheDetail(
      anExchange({
        channel: 'email',
        importedFrom: 'google',
        _links: { self, openInMailbox: { href: message, method: 'GET' } },
      }),
    )

    const open = screen.getByRole('link', { name: 'Ouvrir dans Gmail' })
    expect(open).toHaveAttribute('href', message)
    expect(open).toHaveAttribute('target', '_blank')
  })

  it('offers to open nothing in Gmail when the api does not link to its e-mail', async () => {
    await renderTheDetail(anExchange({ channel: 'email', importedFrom: 'google' }))

    expect(screen.queryByRole('link', { name: 'Ouvrir dans Gmail' })).not.toBeInTheDocument()
  })

  it('says who planned an exchange still to come', async () => {
    await renderTheDetail(anExchange({ status: 'planned', channel: 'videoCall', durationInMinutes: 30 }))

    expect(screen.getByText(/^Planifié le 15 oct\. 2026, .* par Michel Maier$/)).toBeInTheDocument()
  })

  it('shows the link its calendar created once it comes, without reloading the page', async () => {
    const awaiting = anExchange({ status: 'planned', channel: 'videoCall', linkAwaited: true })
    const created = { ...awaiting, link: 'https://meet.google.com/kpq-wxyz-abc', linkAwaited: false }
    theApi.mockImplementation(async (request) =>
      new URL((request as Request).url).pathname === `/api/exchanges/${EXCHANGE}`
        ? Response.json(created)
        : Response.json({}, { status: 404 }),
    )
    await renderTheDetail(awaiting)

    expect(screen.queryByRole('link', { name: 'Rejoindre' })).not.toBeInTheDocument()
    expect(await screen.findByRole('link', { name: 'Rejoindre' }, { timeout: 3_000 })).toHaveAttribute(
      'href',
      'https://meet.google.com/kpq-wxyz-abc',
    )
  })

  it('offers to join a video call to come in a new tab', async () => {
    await renderTheDetail(
      anExchange({ status: 'planned', channel: 'videoCall', link: 'https://meet.google.com/abc-defg-hij' }),
    )

    const join = screen.getByRole('link', { name: 'Rejoindre' })
    expect(join).toHaveAttribute('href', 'https://meet.google.com/abc-defg-hij')
    expect(join).toHaveAttribute('target', '_blank')
  })

  it('shows the place of a meeting to come and the route to it', async () => {
    await renderTheDetail(
      anExchange({ status: 'planned', channel: 'meeting', place: 'Café de Flore, Paris' }),
    )

    expect(screen.getByText('Café de Flore, Paris')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Itinéraire' })).toHaveAttribute(
      'href',
      'https://www.openstreetmap.org/directions?to=Caf%C3%A9+de+Flore%2C+Paris',
    )
  })

  it('offers to call the interlocutor of a call to come at their number', async () => {
    await renderTheDetail(
      anExchange({ status: 'planned', channel: 'call', phoneNumber: '+33 3 88 00 00 42' }),
    )

    expect(screen.getByRole('link', { name: 'Appeler' })).toHaveAttribute('href', 'tel:+33388000042')
  })

  it('offers neither to join nor to call once the exchange is done', async () => {
    await renderTheDetail(anExchange({ channel: 'videoCall', link: 'https://meet.google.com/abc-defg-hij' }))

    expect(screen.queryByRole('link', { name: 'Rejoindre' })).not.toBeInTheDocument()
  })

  it('adds the link of a video call planned without it', async () => {
    const correctVenue = { href: `${self.href}/venue`, method: 'PATCH' }
    const user = await renderTheDetail(
      anExchange({ status: 'planned', channel: 'videoCall', _links: { self, correctVenue } }),
    )

    await user.click(screen.getByRole('button', { name: 'Ajouter le lien' }))

    expect(screen.getByLabelText(/Lien de la visio/)).toHaveValue('')
  })

  it('corrects the place of a meeting from what it is', async () => {
    const correctVenue = { href: `${self.href}/venue`, method: 'PATCH' }
    const user = await renderTheDetail(
      anExchange({
        status: 'planned',
        channel: 'meeting',
        place: 'Café de Flore',
        _links: { self, correctVenue },
      }),
    )

    await user.click(screen.getByRole('button', { name: 'Corriger le lieu' }))

    expect(screen.getByLabelText(/Lieu/)).toHaveValue('Café de Flore')
  })

  it('corrects its text on the revision it was read at when the correction opened, not on one read since', async () => {
    const exchange = anExchange({
      revision: 7,
      _links: { self, correctText: { href: `${self.href}/text`, method: 'PATCH' } },
    })
    const revisions = revisionsSent(exchange)
    const queryClient = aQueryClient()
    const user = await renderTheDetail(exchange, queryClient)

    await user.click(screen.getByRole('button', { name: 'Corriger le texte' }))
    queryClient.setQueryData(exchangeQuery(EXCHANGE).queryKey, { ...exchange, revision: 9 })
    await user.click(await screen.findByRole('button', { name: 'Enregistrer' }))

    await vi.waitFor(() => expect(revisions).toContainEqual({ path: `${self.href}/text`, revision: '7' }))
  })

  it('corrects the place of a meeting on the revision it was read at', async () => {
    const exchange = anExchange({
      revision: 7,
      status: 'planned',
      channel: 'meeting',
      place: 'Café de Flore',
      _links: { self, correctVenue: { href: `${self.href}/venue`, method: 'PATCH' } },
    })
    const revisions = revisionsSent(exchange)
    const user = await renderTheDetail(exchange)

    await user.click(screen.getByRole('button', { name: 'Corriger le lieu' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await vi.waitFor(() => expect(revisions).toContainEqual({ path: `${self.href}/venue`, revision: '7' }))
  })

  it('corrects its facts on the revision it was read at', async () => {
    const exchange = anExchange({ revision: 7 })
    const revisions = revisionsSent(exchange)
    const user = await renderTheDetail(exchange)

    await user.click(screen.getByRole('button', { name: 'Corriger' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await vi.waitFor(() => expect(revisions).toContainEqual({ path: `${self.href}/facts`, revision: '7' }))
  })

  it('shows who noted it as a member without a name while their profile has neither name nor email', async () => {
    await renderTheDetail(
      anExchange({ noted: { at: '2026-10-15T12:35:00+00:00', by: { name: {}, email: null } } }),
    )

    expect(screen.getByText(/^Noté le 15 oct\. 2026, .* par Membre sans nom$/)).toBeInTheDocument()
  })

  it('offers to correct the exchange with what it is', async () => {
    const user = await renderTheDetail(anExchange({}))

    await user.click(screen.getByRole('button', { name: 'Corriger' }))

    expect(screen.getByLabelText('LinkedIn')).toBeChecked()
    expect(screen.getByLabelText(/Texte/)).toHaveValue('Intéressée par une mission en mars')
  })

  it('corrects a missed meeting as missed', async () => {
    const user = await renderTheDetail(anExchange({ channel: 'meeting', status: 'missed' }))

    await user.click(screen.getByRole('button', { name: 'Corriger' }))
    expect(screen.getByLabelText(/Manqué/)).toBeChecked()
  })

  it('corrects a meeting that took place as not missed', async () => {
    const user = await renderTheDetail(anExchange({ channel: 'meeting', status: 'done' }))

    await user.click(screen.getByRole('button', { name: 'Corriger' }))
    expect(screen.getByLabelText(/Manqué/)).not.toBeChecked()
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
        textToRedact: true,
        toRedact: true,
        _links: {
          self,
          correctText: { href: `${self.href}/text`, method: 'PATCH' },
          redact: { href: `${self.href}/text`, method: 'PATCH' },
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
        redacted: {
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
            toRedact: false,
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
        bookedByLink: false,
        createdByAnAI: false,
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
