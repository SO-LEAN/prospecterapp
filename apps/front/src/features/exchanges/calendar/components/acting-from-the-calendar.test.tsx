import { MutationCache, QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { fireEvent, render, screen, waitFor, waitForElementToBeRemoved, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { toast } from 'sonner'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { queryClient as theAppQueryClient } from '@/app/query-client'
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

/** The toaster follows the system's theme, which jsdom does not provide. */
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
const A_MINUTE_MS = 60_000
const TUESDAY = parseDay('2026-11-17')
const EXCHANGE = '0190a1c0-0000-7000-8000-000000000010'
const ADA = '0190a1c0-0000-7000-8000-00000000a000'
const ACME = '0190a1c0-0000-7000-8000-00000000ac00'
const self = { href: `/api/exchanges/${EXCHANGE}`, method: 'GET' }
const move = { href: `/api/exchanges/${EXCHANGE}/moment`, method: 'PATCH' }
const searchContacts = { href: '/api/contacts', method: 'GET' }
const CALENDAR_SELF = { href: '/api/me/calendar', method: 'GET' }

/** The calendar as the API answers it to a member who may plan, unless the test says otherwise. */
const aCalendarOf = (
  items: CalendarExchange[],
  links: { searchContacts?: typeof searchContacts } = { searchContacts },
) => ({
  items,
  toDos: [],
  late: [],
  _links: { self: CALENDAR_SELF, ...links },
})

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
    textToRedact: false,
    toRedact: false,
    organizationIds: [],
    attachmentCount: 0,
    contactToAttach: false,
    bookedByLink: false,
    createdByAnAI: false,
    _links: { self, move },
    ...exchange,
  },
  interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
})

/** The detail as the API answers it. After a move, it offers to move back unless the test says otherwise. */
const theDetail = (
  item: CalendarExchange,
  links: { moveBack?: typeof move } = { moveBack: move },
): ExchangeDetail => ({
  revision: 1,
  ...item.exchange,
  interlocutor: item.interlocutor,
  canceller: null,
  linkAwaited: false,
  text: 'Démonstration',
  noted: { at: NOW.toISOString(), by: { name: { firstName: 'Michel', lastName: 'Maier' } } },
  corrected: null,
  redacted: null,
  attachments: [],
  hiddenAttachments: 0,
  _links: { self, move, interlocutor: { href: `/api/contacts/${ADA}`, method: 'GET' }, ...links },
})

type Answer = { status?: number; body: unknown }
type Answering = (url: URL) => Answer | Promise<Answer>

/** The API's answers by method and path, and what each change sent it. */
function theApiAnswers(answers: Record<string, Answering>) {
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const url = new URL(request.url)
    const answer = (await answers[`${request.method} ${url.pathname}`]?.(url)) ?? { body: aCalendarOf([]) }
    return Response.json(answer.body, { status: answer.status ?? 200 })
  })
}

async function sent(method: string) {
  const changes = theApi.mock.calls.map(([request]) => request as Request).filter((r) => r.method === method)
  return Promise.all(changes.map(async (request) => [new URL(request.url).pathname, await request.json()]))
}

/** Each column of hours is 100 pixels wide and 2 400 high, 100 an hour. Each month cell lies under its own point. */
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

/** Grabs the element, then the window follows the pointer, and the element is drawn again where it is dragged. */
function drag(element: Element, from: { x: number; y: number }, to: { x: number; y: number }) {
  fireEvent.pointerDown(element, { button: 0, pointerId: 1, clientX: from.x, clientY: from.y })
  fireEvent.pointerMove(window, { pointerId: 1, clientX: to.x, clientY: to.y })
  fireEvent.pointerUp(window, { pointerId: 1, clientX: to.x, clientY: to.y })
}

/** As in the app, the toaster is beside the page, not in it. A toast outlives the page that raised it. */
function theToasterOutlivesThePage() {
  render(<Toaster />)
}

/** As in the app, the toast of every mutation shows the API's refusals. */
async function showTheCalendar(view: View, calendar: ReturnType<typeof aCalendarOf>) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
    mutationCache: new MutationCache(theAppQueryClient.getMutationCache().config),
  })
  queryClient.setQueryData(calendarQuery(periodOf(view, TUESDAY)).queryKey, calendar)
  const onShow = vi.fn()
  theToasterOutlivesThePage()
  const page = await renderInARouter(
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
        toDoBox={() => null}
        reviseToDo={() => undefined}
      />
    </QueryClientProvider>,
  )
  theGridIsLaidOut()
  return { onShow, leave: page.unmount }
}

const theToastSaying = async (text: string) => (await screen.findByText(text)).closest('li') as HTMLElement

/** The fake clock also runs the toasts' timers, which still run on their own, so that minutes pass in an instant. */
function theClockRunsTheTimers() {
  vi.useFakeTimers({ toFake: ['Date', 'setTimeout', 'clearTimeout'], shouldAdvanceTime: true })
  vi.setSystemTime(NOW)
  return userEvent.setup({ advanceTimers: vi.advanceTimersByTime })
}

beforeEach(() => {
  vi.useFakeTimers({ toFake: ['Date'] })
  vi.setSystemTime(NOW)
})

afterEach(() => {
  toast.dismiss()
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
      'GET /api/me/calendar': () => ({ body: aCalendarOf([moved]) }),
    })
    await showTheCalendar('week', aCalendarOf([visio]))

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

  it('does not offer to move it back when the API does not link it, its former moment passed', async () => {
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    const moved = aVideoCall(new Date(2026, 10, 18, 9, 0))
    theApiAnswers({
      [`PATCH ${move.href}`]: () => ({ body: theDetail(moved, {}) }),
      'GET /api/me/calendar': () => ({ body: aCalendarOf([moved]) }),
    })
    await showTheCalendar('week', aCalendarOf([visio]))

    drag(screen.getByRole('button', { name: /14h00, Visio/ }), { x: 150, y: 1400 }, { x: 250, y: 900 })

    const told = await theToastSaying('Échange déplacé')
    expect(within(told).queryByRole('button', { name: 'Annuler' })).not.toBeInTheDocument()
  })

  it('withdraws the offer of the move before when the next one cannot be moved back', async () => {
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    const moved = aVideoCall(new Date(2026, 10, 18, 9, 0))
    const movedAgain = aVideoCall(new Date(2026, 10, 19, 10, 0))
    let answered = theDetail(moved)
    let shown = moved
    theApiAnswers({
      [`PATCH ${move.href}`]: () => ({ body: answered }),
      'GET /api/me/calendar': () => ({ body: aCalendarOf([shown]) }),
    })
    await showTheCalendar('week', aCalendarOf([visio]))

    drag(screen.getByRole('button', { name: /14h00, Visio/ }), { x: 150, y: 1400 }, { x: 250, y: 900 })
    const firstMove = await theToastSaying('Échange déplacé')
    expect(within(firstMove).getByRole('button', { name: 'Annuler' })).toBeInTheDocument()
    answered = theDetail(movedAgain, {})
    shown = movedAgain
    drag(await screen.findByRole('button', { name: /9h00, Visio/ }), { x: 250, y: 900 }, { x: 350, y: 1000 })
    await screen.findByRole('button', { name: /10h00, Visio/ })

    await waitFor(() => expect(screen.queryByRole('button', { name: 'Annuler' })).not.toBeInTheDocument())
    expect(await sent('PATCH')).toHaveLength(2)
  })

  it('keeps offering to move it back long after it was moved, however long the member takes', async () => {
    const user = theClockRunsTheTimers()
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    const moved = aVideoCall(new Date(2026, 10, 18, 9, 0))
    theApiAnswers({
      [`PATCH ${move.href}`]: () => ({ body: theDetail(moved) }),
      'GET /api/me/calendar': () => ({ body: aCalendarOf([moved]) }),
    })
    await showTheCalendar('week', aCalendarOf([visio]))

    drag(screen.getByRole('button', { name: /14h00, Visio/ }), { x: 150, y: 1400 }, { x: 250, y: 900 })
    const toast = await theToastSaying('Échange déplacé')
    await vi.advanceTimersByTimeAsync(A_MINUTE_MS)
    await user.click(within(toast).getByRole('button', { name: 'Annuler' }))

    await screen.findByText('Déplacement annulé')
    expect((await sent('PATCH'))[1]).toEqual([
      move.href,
      { occurredAt: new Date(2026, 10, 17, 14, 0).toISOString(), durationInMinutes: 30 },
    ])
  })

  it('offers to move back the last exchange moved only, where it was before its last move', async () => {
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    const moved = aVideoCall(new Date(2026, 10, 18, 9, 0))
    const movedAgain = aVideoCall(new Date(2026, 10, 19, 10, 0))
    let shown = moved
    theApiAnswers({
      [`PATCH ${move.href}`]: () => ({ body: theDetail(shown) }),
      'GET /api/me/calendar': () => ({ body: aCalendarOf([shown]) }),
    })
    await showTheCalendar('week', aCalendarOf([visio]))

    drag(screen.getByRole('button', { name: /14h00, Visio/ }), { x: 150, y: 1400 }, { x: 250, y: 900 })
    await theToastSaying('Échange déplacé')
    shown = movedAgain
    drag(await screen.findByRole('button', { name: /9h00, Visio/ }), { x: 250, y: 900 }, { x: 350, y: 1000 })
    await screen.findByRole('button', { name: /10h00, Visio/ })

    await waitFor(() => expect(screen.getAllByText('Échange déplacé')).toHaveLength(1))
    const lastMove = await theToastSaying('Échange déplacé')
    await userEvent.click(within(lastMove).getByRole('button', { name: 'Annuler' }))
    await screen.findByText('Déplacement annulé')
    expect((await sent('PATCH'))[2]).toEqual([
      move.href,
      { occurredAt: new Date(2026, 10, 18, 9, 0).toISOString(), durationInMinutes: 30 },
    ])
  })

  it('no longer offers to move it back once the calendar is left', async () => {
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    const moved = aVideoCall(new Date(2026, 10, 18, 9, 0))
    theApiAnswers({
      [`PATCH ${move.href}`]: () => ({ body: theDetail(moved) }),
      'GET /api/me/calendar': () => ({ body: aCalendarOf([moved]) }),
    })
    const { leave } = await showTheCalendar('week', aCalendarOf([visio]))

    drag(screen.getByRole('button', { name: /14h00, Visio/ }), { x: 150, y: 1400 }, { x: 250, y: 900 })
    await theToastSaying('Échange déplacé')
    leave()

    await waitForElementToBeRemoved(() => screen.queryByText('Échange déplacé'))
  })

  it('tells a move the API answers once the calendar is left, without offering to move it back', async () => {
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    const moved = aVideoCall(new Date(2026, 10, 18, 9, 0))
    let answer: () => void = () => undefined
    const answered = new Promise<void>((resolve) => {
      answer = () => resolve()
    })
    theApiAnswers({
      [`PATCH ${move.href}`]: async () => {
        await answered
        return { body: theDetail(moved) }
      },
      'GET /api/me/calendar': () => ({ body: aCalendarOf([moved]) }),
    })
    const { leave } = await showTheCalendar('week', aCalendarOf([visio]))

    drag(screen.getByRole('button', { name: /14h00, Visio/ }), { x: 150, y: 1400 }, { x: 250, y: 900 })
    leave()
    answer()

    const told = await theToastSaying('Échange déplacé')
    expect(within(told).queryByRole('button', { name: 'Annuler' })).not.toBeInTheDocument()
  })

  it('no longer offers to move it back once an exchange is opened, which may be moved from there', async () => {
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    const moved = aVideoCall(new Date(2026, 10, 18, 9, 0))
    theApiAnswers({
      [`PATCH ${move.href}`]: () => ({ body: theDetail(moved) }),
      [`GET ${self.href}`]: () => ({ body: theDetail(moved) }),
      'GET /api/me/calendar': () => ({ body: aCalendarOf([moved]) }),
    })
    await showTheCalendar('week', aCalendarOf([visio]))

    drag(screen.getByRole('button', { name: /14h00, Visio/ }), { x: 150, y: 1400 }, { x: 250, y: 900 })
    await theToastSaying('Échange déplacé')
    await userEvent.click(await screen.findByRole('button', { name: /9h00, Visio/ }))

    await waitForElementToBeRemoved(() => screen.queryByText('Échange déplacé'))
  })

  it('shows it where it is dropped, then back at its place, the refusal told, when the API refuses', async () => {
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    let refuse: () => void = () => undefined
    const refused = new Promise<void>((resolve) => {
      refuse = () => resolve()
    })
    theApiAnswers({
      [`PATCH ${move.href}`]: async () => {
        await refused
        return {
          status: 409,
          body: { type: 'about:blank', title: 'Only an upcoming exchange may change', status: 409 },
        }
      },
      'GET /api/me/calendar': () => ({ body: aCalendarOf([visio]) }),
    })
    await showTheCalendar('week', aCalendarOf([visio]))
    const theDayOf = (name: string) => screen.getByRole('list', { name })

    drag(screen.getByRole('button', { name: /14h00, Visio/ }), { x: 150, y: 1400 }, { x: 250, y: 900 })

    expect(
      await within(theDayOf('mercredi 18 novembre 2026')).findByRole('button', { name: /9h00, Visio/ }),
    ).toBeInTheDocument()
    refuse()
    expect(await screen.findByText('Only an upcoming exchange may change')).toBeInTheDocument()
    expect(
      await within(theDayOf('mardi 17 novembre 2026')).findByRole('button', { name: /14h00, Visio/ }),
    ).toBeInTheDocument()
    expect(within(theDayOf('mercredi 18 novembre 2026')).queryByRole('button')).not.toBeInTheDocument()
    expect(await sent('PATCH')).toHaveLength(1)
  })

  it('stretches it by its end to the quarter the pointer reaches, a click opening it again', async () => {
    const visio = aVideoCall(new Date(2026, 10, 17, 14, 0))
    const stretchedVisio = aVideoCall(new Date(2026, 10, 17, 14, 0), { durationInMinutes: 60 })
    theApiAnswers({
      [`PATCH ${move.href}`]: () => ({ body: theDetail(stretchedVisio) }),
      'GET /api/me/calendar': () => ({ body: aCalendarOf([stretchedVisio]) }),
      [`GET ${self.href}`]: () => ({ body: theDetail(stretchedVisio) }),
    })
    await showTheCalendar('week', aCalendarOf([visio]))
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
    await showTheCalendar('month', aCalendarOf([visio]))
    document.elementFromPoint = () => theCellOf('2026-11-20')

    drag(screen.getByRole('button', { name: /14h00, Visio/ }), { x: 10, y: 10 }, { x: 300, y: 200 })

    await waitFor(async () =>
      expect(await sent('PATCH')).toEqual([
        [move.href, { occurredAt: new Date(2026, 10, 20, 14, 0).toISOString(), durationInMinutes: 30 }],
      ]),
    )
  })

  it('leaves an exchange past where it is: the API does not let it move', async () => {
    await showTheCalendar(
      'week',
      aCalendarOf([aVideoCall(new Date(2026, 10, 17, 14, 0), { status: 'done', _links: { self } })]),
    )
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
    const { onShow } = await showTheCalendar('week', aCalendarOf([visio]))

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

describe('an exchange opened from the calendar, with no contact to show', () => {
  it('leads an exchange with an organization to its card, without a contact', async () => {
    const meeting = {
      ...aVideoCall(new Date(2026, 10, 17, 14, 0)),
      interlocutor: { kind: 'organization', id: ACME, name: 'ACME', presence: null },
    } satisfies CalendarExchange
    theApiAnswers({ [`GET ${self.href}`]: () => ({ body: theDetail(meeting) }) })
    await showTheCalendar('week', aCalendarOf([meeting]))

    await userEvent.click(screen.getByRole('button', { name: /14h00, Visio avec ACME/ }))

    const dialog = await screen.findByRole('dialog', { name: 'Échange' })
    expect(await within(dialog).findByRole('link', { name: 'Ouvrir la fiche' })).toHaveAttribute(
      'href',
      `/organizations/${ACME}`,
    )
    expect(within(dialog).queryByRole('region', { name: 'Le contact' })).not.toBeInTheDocument()
  })

  it('leads an exchange with a forgotten contact nowhere', async () => {
    const forgotten = aVideoCall(new Date(2026, 10, 17, 14, 0), { status: 'done', _links: { self } })
    forgotten.interlocutor = { kind: 'person', id: ADA, name: null, presence: 'forgotten' }
    const { interlocutor: _none, ...links } = theDetail(forgotten)._links
    theApiAnswers({ [`GET ${self.href}`]: () => ({ body: { ...theDetail(forgotten), _links: links } }) })
    await showTheCalendar('week', aCalendarOf([forgotten]))

    await userEvent.click(screen.getByRole('button', { name: /14h00, Visio/ }))

    const dialog = await screen.findByRole('dialog', { name: 'Échange' })
    expect(await within(dialog).findByText('Démonstration')).toBeInTheDocument()
    expect(within(dialog).queryByRole('region', { name: 'Le contact' })).not.toBeInTheDocument()
    expect(within(dialog).queryByRole('link', { name: 'Ouvrir la fiche' })).not.toBeInTheDocument()
  })
})

describe('a free slot', () => {
  it('is not chosen by a member who may not plan: the API offers no search of the contacts', async () => {
    await showTheCalendar('week', aCalendarOf([], {}))
    const thursday = screen.getByRole('list', { name: 'jeudi 19 novembre 2026' })

    fireEvent.pointerDown(thursday, { button: 0, pointerId: 1, clientX: 350, clientY: 1010 })
    fireEvent.pointerUp(thursday, { pointerId: 1, clientX: 350, clientY: 1010 })

    expect(screen.queryByRole('dialog', { name: 'Planifier' })).not.toBeInTheDocument()
    expect(thursday).not.toHaveClass('cursor-cell')
  })

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
    await showTheCalendar('week', aCalendarOf([]))
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
    await showTheCalendar('week', aCalendarOf([]))
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
