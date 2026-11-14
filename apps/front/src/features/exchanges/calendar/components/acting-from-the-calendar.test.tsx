import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { fireEvent, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { Toaster } from '@/shared/ui/sonner'
import { renderInARouter } from '@/test/render-in-a-router'
import type { ExchangeDetail } from '../../queries'
import { parseDay, periodOf, type View } from '../dates'
import { type CalendarExchange, calendarQuery } from '../queries'
import { CalendarPage } from './calendar-page'

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

const NOW = new Date(2026, 10, 13, 10, 0)
const TUESDAY = parseDay('2026-11-17')
const EXCHANGE = '0190a1c0-0000-7000-8000-000000000010'
const ADA = '0190a1c0-0000-7000-8000-00000000a000'
const self = { href: `/api/exchanges/${EXCHANGE}`, method: 'GET' }
const move = { href: `/api/exchanges/${EXCHANGE}/moment`, method: 'PATCH' }

const aVideoCall = (
  occurredAt: Date,
  exchange: Partial<CalendarExchange['exchange']> = {},
): CalendarExchange => ({
  exchange: {
    id: EXCHANGE,
    channel: 'videoCall',
    direction: 'outbound',
    occurredAt: occurredAt.toISOString(),
    status: 'planned',
    durationInMinutes: 30,
    textFlagged: false,
    textToMakeConform: false,
    toMakeConform: false,
    organizationIds: [],
    attachmentCount: 0,
    _links: { self, move },
    ...exchange,
  },
  interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
})

const theDetail = (item: CalendarExchange): ExchangeDetail => ({
  ...item.exchange,
  interlocutor: item.interlocutor,
  canceller: null,
  text: 'Démonstration',
  noted: { at: NOW.toISOString(), by: { name: { firstName: 'Michel', lastName: 'Maier' } } },
  corrected: null,
  madeConform: null,
  attachments: [],
  hiddenAttachments: 0,
  _links: { self, move, interlocutor: { href: `/api/contacts/${ADA}`, method: 'GET' } },
})

type Answer = { status?: number; body: unknown }

/** The API by method and path; what it was sent with a change. */
function theApiAnswers(answers: Record<string, (url: URL) => Answer>) {
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const url = new URL(request.url)
    const answer = answers[`${request.method} ${url.pathname}`]?.(url) ?? { body: { items: [] } }
    return Response.json(answer.body, { status: answer.status ?? 200 })
  })
}

async function sent(method: string) {
  const changes = theApi.mock.calls.map(([request]) => request as Request).filter((r) => r.method === method)
  return Promise.all(changes.map(async (request) => [new URL(request.url).pathname, await request.json()]))
}

/** Each column of hours 100 pixels wide and 2 400 high, 100 an hour; a cell of the month under its own point. */
function theGridIsLaidOut() {
  vi.spyOn(Element.prototype, 'getBoundingClientRect').mockImplementation(
    () => ({ top: 0, height: 2400, left: 0, width: 100 }) as DOMRect,
  )
  document.elementFromPoint = (x: number) => {
    const columns = document.querySelectorAll('[data-hours]')
    return columns[Math.floor(x / 100)] ?? null
  }
}

const theCellOf = (day: string) => document.querySelector(`[data-day="${day}"]`) as HTMLElement

/** Grabbed on the element, then followed by the window, the element drawn again where it is dragged. */
function drag(element: Element, from: { x: number; y: number }, to: { x: number; y: number }) {
  fireEvent.pointerDown(element, { button: 0, pointerId: 1, clientX: from.x, clientY: from.y })
  fireEvent.pointerMove(window, { pointerId: 1, clientX: to.x, clientY: to.y })
  fireEvent.pointerUp(window, { pointerId: 1, clientX: to.x, clientY: to.y })
}

async function showTheCalendar(view: View, items: CalendarExchange[]) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(calendarQuery(periodOf(view, TUESDAY)).queryKey, { items })
  const onShow = vi.fn()
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <CalendarPage
        view={view}
        day={TUESDAY}
        onShow={onShow}
        contactSummary={(id) => <p>{`Résumé du contact ${id}`}</p>}
        interlocutorSearch={(onChoose) => (
          <button type="button" onClick={() => onChoose({ kind: 'person', id: ADA, name: 'Ada Lovelace' })}>
            Ada Lovelace
          </button>
        )}
      />
      <Toaster />
    </QueryClientProvider>,
  )
  theGridIsLaidOut()
  return onShow
}

beforeEach(() => {
  vi.useFakeTimers({ toFake: ['Date'] })
  vi.setSystemTime(NOW)
})

afterEach(() => {
  vi.useRealTimers()
  vi.restoreAllMocks()
  theApi.mockReset()
})

describe('dragging an exchange to come', () => {
  it('moves it to the day and the quarter it is dropped on, its toast moving it back', async () => {
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    const moved = aVideoCall(new Date(2026, 10, 18, 9, 0))
    theApiAnswers({
      [`PATCH ${move.href}`]: () => ({ body: theDetail(moved) }),
      'GET /api/me/calendar': () => ({ body: { items: [moved] } }),
    })
    await showTheCalendar('week', [visio])

    drag(screen.getByRole('button', { name: /14h00, Visio/ }), { x: 150, y: 1400 }, { x: 250, y: 900 })

    expect(
      await within(screen.getByRole('list', { name: 'mercredi 18 novembre 2026' })).findByRole('button', {
        name: /9h00, Visio avec Ada Lovelace/,
      }),
    ).toBeInTheDocument()
    const toast = (await screen.findByText('Échange déplacé')).closest('li') as HTMLElement
    await userEvent.click(within(toast).getByRole('button', { name: 'Annuler' }))
    await screen.findByText('Déplacement annulé')
    expect(await sent('PATCH')).toEqual([
      [move.href, { occurredAt: new Date(2026, 10, 18, 9, 0).toISOString(), durationInMinutes: 30 }],
      [move.href, { occurredAt: new Date(2026, 10, 17, 14, 0).toISOString(), durationInMinutes: 30 }],
    ])
  })

  it('goes back to its place when the API refuses to move it', async () => {
    theApiAnswers({
      [`PATCH ${move.href}`]: () => ({
        status: 409,
        body: { type: 'about:blank', title: 'Only an upcoming exchange may change', status: 409 },
      }),
    })
    await showTheCalendar('week', [aVideoCall(new Date(2026, 10, 17, 14, 0))])

    drag(screen.getByRole('button', { name: /14h00, Visio/ }), { x: 150, y: 1400 }, { x: 250, y: 900 })

    await waitFor(() =>
      expect(
        within(screen.getByRole('list', { name: 'mardi 17 novembre 2026' })).getByRole('button', {
          name: /14h00, Visio/,
        }),
      ).toBeInTheDocument(),
    )
    expect(await sent('PATCH')).toHaveLength(1)
  })

  it('stretches it by its end to the quarter the pointer reaches, a click opening it again', async () => {
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    const stretchedVisio = aVideoCall(new Date(2026, 10, 17, 14, 0), { durationInMinutes: 60 })
    theApiAnswers({
      [`PATCH ${move.href}`]: () => ({ body: theDetail(stretchedVisio) }),
      'GET /api/me/calendar': () => ({ body: { items: [stretchedVisio] } }),
      [`GET ${self.href}`]: () => ({ body: theDetail(stretchedVisio) }),
    })
    await showTheCalendar('week', [visio])
    const chip = screen.getByRole('button', { name: /14h00, Visio/ })

    drag(chip.querySelector('[data-stretch]') as Element, { x: 150, y: 1450 }, { x: 150, y: 1490 })

    await waitFor(async () =>
      expect(await sent('PATCH')).toEqual([
        [move.href, { occurredAt: new Date(2026, 10, 17, 14, 0).toISOString(), durationInMinutes: 60 }],
      ]),
    )
    await userEvent.click(screen.getByRole('button', { name: /14h00, Visio/ }))
    expect(await screen.findByRole('dialog', { name: 'Échange' })).toBeInTheDocument()
  })

  it('changes its day alone in the month, its hour kept', async () => {
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    theApiAnswers({ [`PATCH ${move.href}`]: () => ({ body: theDetail(visio) }) })
    await showTheCalendar('month', [visio])
    document.elementFromPoint = () => theCellOf('2026-11-20')

    drag(screen.getByRole('button', { name: /14h00, Visio/ }), { x: 10, y: 10 }, { x: 300, y: 200 })

    await waitFor(async () =>
      expect(await sent('PATCH')).toEqual([
        [move.href, { occurredAt: new Date(2026, 10, 20, 14, 0).toISOString(), durationInMinutes: 30 }],
      ]),
    )
  })

  it('leaves an exchange past where it is: the API does not let it move', async () => {
    await showTheCalendar('week', [
      aVideoCall(new Date(2026, 10, 17, 14, 0), { status: 'done', _links: { self } }),
    ])
    const done = screen.getByRole('button', { name: /14h00, Visio/ })

    drag(done, { x: 150, y: 1400 }, { x: 250, y: 900 })

    expect(done).not.toHaveAttribute('data-movable')
    expect(
      within(screen.getByRole('list', { name: 'mardi 17 novembre 2026' })).getByRole('button', {
        name: /14h00, Visio/,
      }),
    ).toBeInTheDocument()
    expect(await sent('PATCH')).toEqual([])
  })
})

describe('an exchange opened from the calendar', () => {
  it('shows all of it, its contact and their last exchanges, leads to their card and closes by Échap', async () => {
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    theApiAnswers({
      [`GET ${self.href}`]: () => ({ body: theDetail(visio) }),
      [`GET /api/contacts/${ADA}/exchanges`]: () => ({
        body: {
          items: [{ ...visio.exchange, status: 'done', channel: 'call', text: 'Premier appel' }],
          _links: { self: { href: `/api/contacts/${ADA}/exchanges`, method: 'GET' } },
        },
      }),
    })
    const onShow = await showTheCalendar('week', [visio])

    await userEvent.click(screen.getByRole('button', { name: /14h00, Visio/ }))

    const dialog = await screen.findByRole('dialog', { name: 'Échange' })
    expect(await within(dialog).findByText('Démonstration')).toBeInTheDocument()
    expect(within(dialog).getByRole('button', { name: /Déplacer/ })).toBeInTheDocument()
    expect(within(dialog).getByText(`Résumé du contact ${ADA}`)).toBeInTheDocument()
    expect(await within(dialog).findByText('Premier appel')).toBeInTheDocument()
    expect(within(dialog).getByRole('link', { name: 'Ouvrir la fiche' })).toHaveAttribute(
      'href',
      `/contacts/${ADA}`,
    )
    await userEvent.keyboard('m')
    expect(onShow).not.toHaveBeenCalled()
    await userEvent.keyboard('{Escape}')
    await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument())
  })
})

describe('a free slot', () => {
  it('plans there with the contact chosen, at its moment, for half an hour', async () => {
    theApiAnswers({
      [`GET /api/contacts/${ADA}/exchanges`]: () => ({
        body: {
          items: [],
          _links: {
            self: { href: `/api/contacts/${ADA}/exchanges`, method: 'GET' },
            planExchange: { href: `/api/contacts/${ADA}/upcoming-exchanges`, method: 'POST' },
          },
        },
      }),
      [`GET /api/contacts/${ADA}/meeting-place`]: () => ({ body: { address: null } }),
    })
    await showTheCalendar('week', [])
    const thursday = screen.getByRole('list', { name: 'jeudi 19 novembre 2026' })

    fireEvent.pointerDown(thursday, { button: 0, pointerId: 1, clientX: 350, clientY: 1010 })
    fireEvent.pointerUp(thursday, { pointerId: 1, clientX: 350, clientY: 1010 })

    const dialog = await screen.findByRole('dialog', { name: 'Planifier' })
    await userEvent.click(within(dialog).getByRole('button', { name: 'Ada Lovelace' }))
    expect(await within(dialog).findByText('Avec Ada Lovelace')).toBeInTheDocument()
    expect(await within(dialog).findByLabelText('Date')).toHaveValue('2026-11-19')
    expect(within(dialog).getByLabelText('Heure')).toHaveValue('10:00')
    expect(within(dialog).getByLabelText('Durée')).toHaveValue(30)
  })

  it('says when the contact chosen may not be planned with, and goes back to the search', async () => {
    theApiAnswers({
      [`GET /api/contacts/${ADA}/exchanges`]: () => ({
        body: { items: [], _links: { self: { href: `/api/contacts/${ADA}/exchanges`, method: 'GET' } } },
      }),
    })
    await showTheCalendar('week', [])
    const thursday = screen.getByRole('list', { name: 'jeudi 19 novembre 2026' })
    fireEvent.pointerDown(thursday, { button: 0, pointerId: 1, clientX: 350, clientY: 1010 })
    fireEvent.pointerUp(thursday, { pointerId: 1, clientX: 350, clientY: 1010 })
    const dialog = await screen.findByRole('dialog', { name: 'Planifier' })

    await userEvent.click(within(dialog).getByRole('button', { name: 'Ada Lovelace' }))

    expect(
      await within(dialog).findByText('Vous ne pouvez pas planifier d’échange avec Ada Lovelace.'),
    ).toBeInTheDocument()
    await userEvent.click(within(dialog).getByRole('button', { name: 'Retour' }))
    expect(within(dialog).getByRole('button', { name: 'Ada Lovelace' })).toBeInTheDocument()
  })
})
