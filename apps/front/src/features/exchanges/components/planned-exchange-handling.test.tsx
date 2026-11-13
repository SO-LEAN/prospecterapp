import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { Toaster } from '@/shared/ui/sonner'
import { renderInARouter } from '@/test/render-in-a-router'
import { UNDO_DELAY_MS } from '../mutations'
import { type ExchangeDetail, exchangeQuery } from '../queries'
import { ExchangeDetailSheet } from './exchange-detail'

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

const EXCHANGE = '0190a1c0-0000-7000-8000-000000000010'
const ADA = '0190a1c0-0000-7000-8000-000000000001'
const ACME = '0190a1c0-0000-7000-8000-000000000002'
const self = { href: `/api/exchanges/${EXCHANGE}`, method: 'GET' }
const ITS_TIME_HAS_COME = '2026-11-10T13:00:00+00:00'
const handling = {
  markDone: { href: `${self.href}/done`, method: 'POST' },
  markMissed: { href: `${self.href}/missed`, method: 'POST' },
  move: { href: `${self.href}/moment`, method: 'PATCH' },
  cancel: { href: `${self.href}/cancellation`, method: 'POST' },
}

const aPlannedExchange = (exchange: Partial<ExchangeDetail>): ExchangeDetail => ({
  id: EXCHANGE,
  interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
  channel: 'videoCall',
  direction: 'outbound',
  canceller: null,
  occurredAt: '2026-11-17T13:00:00+00:00',
  status: 'planned',
  durationInMinutes: 30,
  text: 'Démonstration',
  textFlagged: false,
  textToMakeConform: false,
  toMakeConform: false,
  organizationIds: [],
  noted: { at: '2026-11-13T09:00:00+00:00', by: { name: { firstName: 'Michel', lastName: 'Maier' } } },
  corrected: null,
  madeConform: null,
  attachments: [],
  hiddenAttachments: 0,
  _links: { self, ...handling },
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
      <Toaster />
    </QueryClientProvider>,
  )
  return userEvent.setup({
    advanceTimers: (delay) => {
      if (vi.isFakeTimers()) {
        vi.advanceTimersByTime(delay)
      }
    },
    pointerEventsCheck: 0,
  })
}

/** The requests the API received: their method, their path and their body. */
type Sent = [method: string, path: string, body: Record<string, unknown> | null]

async function requestsToTheApi(): Promise<Sent[]> {
  return Promise.all(
    theApi.mock.calls.map(async ([request]): Promise<Sent> => {
      const sent = request as Request
      const body = await sent.clone().text()
      return [sent.method, new URL(sent.url).pathname, body === '' ? null : JSON.parse(body)]
    }),
  )
}

afterEach(() => {
  vi.useRealTimers()
  theApi.mockReset()
})

describe('handling a planned exchange', () => {
  it('offers only what its links offer: before its time, moving or cancelling it', async () => {
    await renderTheDetail(
      aPlannedExchange({ _links: { self, move: handling.move, cancel: handling.cancel } }),
    )

    expect(screen.getByRole('button', { name: 'Déplacer' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Annuler l’échange' })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Fait' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Manqué' })).not.toBeInTheDocument()
  })

  it('marks it done with its report written from its agenda, then offers to plan what follows on its channel', async () => {
    theApi.mockResolvedValue(Response.json(aPlannedExchange({ status: 'done', _links: { self } })))
    const user = await renderTheDetail(aPlannedExchange({ occurredAt: ITS_TIME_HAS_COME }))

    await user.click(screen.getByRole('button', { name: 'Fait' }))
    const report = screen.getByLabelText(/Ce qui s’est dit/)
    expect(report).toHaveValue('Démonstration')
    await user.clear(report)
    await user.type(report, 'Mission de 3 mois en mars')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByText(/Planifier la suite avec ce même interlocuteur/)).toBeInTheDocument()
    expect(await requestsToTheApi()).toEqual([
      [
        'POST',
        `/api/exchanges/${EXCHANGE}/done`,
        { occurredAt: '2026-11-10T13:00:00.000Z', text: 'Mission de 3 mois en mars' },
      ],
    ])
    await user.click(screen.getByRole('button', { name: 'Planifier la suite' }))
    expect(screen.getByLabelText('Visio')).toBeChecked()
  })

  it('lets what follows be left in a click', async () => {
    theApi.mockResolvedValue(Response.json(aPlannedExchange({ status: 'done', _links: { self } })))
    const user = await renderTheDetail(aPlannedExchange({ occurredAt: ITS_TIME_HAS_COME }))

    await user.click(screen.getByRole('button', { name: 'Fait' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))
    await user.click(await screen.findByRole('button', { name: 'Plus tard' }))

    expect(screen.getByText('Visio, Envoyé')).toBeInTheDocument()
  })

  it('moves it to another moment, its duration kept', async () => {
    theApi.mockResolvedValue(Response.json(aPlannedExchange({ occurredAt: '2099-11-18T08:00:00+00:00' })))
    const user = await renderTheDetail(aPlannedExchange({}))

    await user.click(screen.getByRole('button', { name: 'Déplacer' }))
    expect(screen.getByText('Sa durée reste la même.')).toBeInTheDocument()
    await user.clear(screen.getByLabelText('Date'))
    await user.type(screen.getByLabelText('Date'), '2099-11-18')
    await user.clear(screen.getByLabelText('Heure'))
    await user.type(screen.getByLabelText('Heure'), '09:00')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await requestsToTheApi()).toEqual([
      [
        'PATCH',
        `/api/exchanges/${EXCHANGE}/moment`,
        { occurredAt: new Date(2099, 10, 18, 9, 0).toISOString() },
      ],
    ])
  })

  it('marks it missed once its toast no longer lets it be taken back', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    theApi.mockResolvedValue(Response.json(aPlannedExchange({ status: 'missed', _links: { self } })))
    const user = await renderTheDetail(aPlannedExchange({}))

    await user.click(screen.getByRole('button', { name: 'Manqué' }))
    expect(await screen.findByText('Échange marqué manqué')).toBeInTheDocument()
    expect(theApi).not.toHaveBeenCalled()
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))

    expect(await requestsToTheApi()).toEqual([['POST', `/api/exchanges/${EXCHANGE}/missed`, null]])
  })

  it('cancels it saying who called it off, unless its toast took it back', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    const user = await renderTheDetail(aPlannedExchange({}))

    await user.click(screen.getByRole('button', { name: 'Annuler l’échange' }))
    const dialog = await screen.findByRole('dialog', { name: /Annuler cet échange/ })
    await user.click(within(dialog).getByLabelText('Le contact'))
    await user.click(within(dialog).getByRole('button', { name: 'Annuler l’échange' }))
    const toast = (await screen.findByText('Échange annulé')).closest('li') as HTMLElement
    await user.click(within(toast).getByRole('button', { name: 'Annuler' }))
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))

    expect(theApi).not.toHaveBeenCalled()
  })

  it('sends who called it off once the toast is gone', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    theApi.mockResolvedValue(Response.json(aPlannedExchange({ status: 'cancelled', _links: { self } })))
    const user = await renderTheDetail(aPlannedExchange({}))

    await user.click(screen.getByRole('button', { name: 'Annuler l’échange' }))
    const dialog = await screen.findByRole('dialog', { name: /Annuler cet échange/ })
    await user.click(within(dialog).getByLabelText('Le contact'))
    await user.click(within(dialog).getByRole('button', { name: 'Annuler l’échange' }))
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))

    expect(await requestsToTheApi()).toEqual([
      ['POST', `/api/exchanges/${EXCHANGE}/cancellation`, { canceller: 'contact' }],
    ])
  })

  it('shows a cancelled exchange struck through, with who called it off', async () => {
    await renderTheDetail(aPlannedExchange({ status: 'cancelled', canceller: 'contact', _links: { self } }))

    expect(screen.getByText('Visio, Annulé')).toHaveClass('line-through')
    expect(screen.getByRole('time')).toHaveClass('line-through')
    expect(screen.getByText('Annulé par le contact')).toBeInTheDocument()
  })

  it('shows an exchange done neither struck through nor cancelled by anybody', async () => {
    await renderTheDetail(
      aPlannedExchange({ status: 'done', occurredAt: ITS_TIME_HAS_COME, _links: { self } }),
    )

    expect(screen.getByText('Visio, Envoyé')).not.toHaveClass('line-through')
    expect(screen.getByRole('time')).not.toHaveClass('line-through')
    expect(screen.queryByText(/^Annulé par/)).not.toBeInTheDocument()
  })

  it('plans what follows with the contact it was with', async () => {
    const user = await renderTheReport(aPlannedExchange({ occurredAt: ITS_TIME_HAS_COME }))

    await user.click(await screen.findByRole('button', { name: 'Planifier la suite' }))
    await user.click(screen.getByRole('button', { name: 'Demain 9h' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await expect.poll(planningSent).toMatchObject({ path: `/api/contacts/${ADA}/upcoming-exchanges` })
    expect(await screen.findByRole('button', { name: 'Supprimer' })).toBeInTheDocument()
  })

  it('plans what follows with the organization itself it was with', async () => {
    const user = await renderTheReport(
      aPlannedExchange({
        occurredAt: ITS_TIME_HAS_COME,
        interlocutor: { kind: 'organization', id: ACME, name: 'ACME', presence: null },
      }),
    )

    await user.click(await screen.findByRole('button', { name: 'Planifier la suite' }))
    await user.click(screen.getByRole('button', { name: 'Demain 9h' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await expect.poll(planningSent).toMatchObject({
      path: `/api/organizations/${ACME}/upcoming-exchanges`,
      body: { personId: null, exchange: { channel: 'videoCall' } },
    })
  })

  it('proposes nothing to follow with a forgotten contact', async () => {
    await renderTheReport(
      aPlannedExchange({
        occurredAt: ITS_TIME_HAS_COME,
        interlocutor: { kind: 'person', id: ADA, name: null, presence: 'forgotten' },
      }),
    )

    expect(await screen.findByRole('button', { name: 'Supprimer' })).toBeInTheDocument()
    expect(screen.queryByText(/Planifier la suite/)).not.toBeInTheDocument()
  })

  it('joins the files chosen with its report', async () => {
    const user = await renderTheDetail(aPlannedExchange({ occurredAt: ITS_TIME_HAS_COME }))
    theApi.mockResolvedValue(Response.json(aPlannedExchange({ status: 'done', _links: { self } })))

    await user.click(screen.getByRole('button', { name: 'Fait' }))
    await user.upload(
      screen.getByLabelText('choisissez-les'),
      new File(['devis'], 'devis.pdf', { type: 'application/pdf' }),
    )
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await expect
      .poll(async () => (await requestsToTheApi()).map(([method, path]) => `${method} ${path}`))
      .toContain('POST /api/uploads')
  })

  it('refuses a report in the future, sending nothing', async () => {
    const user = await renderTheDetail(aPlannedExchange({ occurredAt: ITS_TIME_HAS_COME }))

    await user.click(screen.getByRole('button', { name: 'Fait' }))
    await user.clear(screen.getByLabelText('Moment'))
    await user.type(screen.getByLabelText('Moment'), '2099-01-01T09:00')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByText('Un échange ne peut pas être dans le futur.')).toBeInTheDocument()
    expect(theApi).not.toHaveBeenCalled()
  })

  it('goes back to the exchange when the report or the move is left', async () => {
    const user = await renderTheDetail(aPlannedExchange({ occurredAt: ITS_TIME_HAS_COME }))

    const sheet = screen.getByRole('dialog', { name: 'Échange' })

    await user.click(within(sheet).getByRole('button', { name: 'Fait' }))
    await user.click(within(sheet).getByRole('button', { name: 'Annuler' }))
    await user.click(within(sheet).getByRole('button', { name: 'Déplacer' }))
    await user.click(within(sheet).getByRole('button', { name: 'Annuler' }))

    expect(screen.getByRole('button', { name: 'Fait' })).toBeInTheDocument()
    expect(theApi).not.toHaveBeenCalled()
  })

  it('moves it by a shortcut, then goes back to the exchange', async () => {
    theApi.mockResolvedValue(Response.json(aPlannedExchange({})))
    const user = await renderTheDetail(aPlannedExchange({}))

    await user.click(screen.getByRole('button', { name: 'Déplacer' }))
    expect(screen.queryByRole('alert')).not.toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Dans 1 semaine' }))
    const aWeekAhead = new Date(Date.now() + 7 * 24 * 60 * 60_000)
    expect(screen.getByLabelText('Date')).toHaveValue(
      `${aWeekAhead.getFullYear()}-${String(aWeekAhead.getMonth() + 1).padStart(2, '0')}-${String(aWeekAhead.getDate()).padStart(2, '0')}`,
    )
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByRole('button', { name: 'Déplacer' })).toBeInTheDocument()
    expect(await requestsToTheApi()).toHaveLength(1)
  })

  it('refuses a move into the past, sending nothing', async () => {
    const user = await renderTheDetail(aPlannedExchange({}))

    await user.click(screen.getByRole('button', { name: 'Déplacer' }))
    await user.clear(screen.getByLabelText('Date'))
    await user.type(screen.getByLabelText('Date'), '2020-01-01')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByText('Un échange planifié est dans le futur.')).toBeInTheDocument()
    expect(theApi).not.toHaveBeenCalled()
  })

  it('tells the refusal of the API when the exchange no longer moves', async () => {
    theApi.mockResolvedValue(
      Response.json(
        {
          type: 'about:blank',
          title: 'Only an upcoming exchange may change',
          status: 409,
          reason: 'notUpcoming',
        },
        { status: 409, headers: { 'Content-Type': 'application/problem+json' } },
      ),
    )
    const user = await renderTheDetail(aPlannedExchange({}))

    await user.click(screen.getByRole('button', { name: 'Déplacer' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/Cet échange n’est plus à venir/)
  })

  it('cancels it without saying who called it off, the choice by default, the dialog closed', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    theApi.mockResolvedValue(Response.json(aPlannedExchange({ status: 'cancelled', _links: { self } })))
    const user = await renderTheDetail(aPlannedExchange({}))

    await user.click(screen.getByRole('button', { name: 'Annuler l’échange' }))
    const dialog = await screen.findByRole('dialog', { name: /Annuler cet échange/ })
    expect(within(dialog).getByLabelText('Ne pas préciser')).toBeChecked()
    await user.click(within(dialog).getByRole('button', { name: 'Annuler l’échange' }))
    await act(() => vi.advanceTimersByTimeAsync(UNDO_DELAY_MS))

    expect(screen.queryByRole('dialog', { name: /Annuler cet échange/ })).not.toBeInTheDocument()
    expect(await requestsToTheApi()).toEqual([
      ['POST', `/api/exchanges/${EXCHANGE}/cancellation`, { canceller: null }],
    ])
  })
})

/** The planning the API received, among the readings of what it overlaps. */
async function planningSent() {
  const planning = (await requestsToTheApi()).find(
    ([method, path]) => method === 'POST' && path.endsWith('/upcoming-exchanges'),
  )
  return planning && { path: planning[1], body: planning[2] }
}

/** Its report saved as it was planned, the API answering the gesture then a planning. */
async function renderTheReport(exchange: ExchangeDetail) {
  theApi.mockImplementation(async (request) =>
    new URL((request as Request).url).pathname.endsWith('/done')
      ? Response.json({
          ...exchange,
          status: 'done',
          _links: { self, eraseExchange: { href: self.href, method: 'DELETE' } },
        })
      : Response.json({ items: [], _links: { self } }, { status: 201 }),
  )
  const user = await renderTheDetail(exchange)
  await user.click(screen.getByRole('button', { name: 'Fait' }))
  await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

  return user
}
