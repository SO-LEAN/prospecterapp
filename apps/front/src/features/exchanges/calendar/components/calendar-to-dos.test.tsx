import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { fireEvent, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import { parseDay, periodOf, type View } from '../dates'
import { type Calendar, type CalendarExchange, type CalendarToDo, calendarQuery } from '../queries'
import { CalendarPage } from './calendar-page'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const NOW = new Date(2026, 10, 13, 10, 0)
const TODAY = parseDay('2026-11-13')
const CALENDAR_LINKS = { self: { href: '/api/me/calendar', method: 'GET' } }

let identity = 0
const aToDo = (text: string, dueOn: string, toDo: Partial<CalendarToDo> = {}): CalendarToDo => {
  const id = `0190a1c0-0000-7000-8000-${String(++identity).padStart(12, '0')}`
  return {
    id,
    text,
    dueOn,
    dueAt: null,
    doneAt: null,
    heldBy: { name: { firstName: 'Ada', lastName: 'Lovelace' } },
    contact: null,
    _links: {
      self: { href: `/api/to-dos/${id}`, method: 'GET' },
      revise: { href: `/api/to-dos/${id}`, method: 'PATCH' },
      tickOff: { href: `/api/to-dos/${id}/done`, method: 'POST' },
    },
    ...toDo,
  }
}

const aPlannedCall = (occurredAt: Date): CalendarExchange => ({
  exchange: {
    id: '0190a1c0-0000-7000-8000-00000000e000',
    channel: 'call',
    direction: 'outbound',
    occurredAt: occurredAt.toISOString(),
    status: 'planned',
    durationInMinutes: 30,
    textFlagged: false,
    textToMakeConform: false,
    toMakeConform: false,
    organizationIds: [],
    attachmentCount: 0,
    _links: { self: { href: '/api/exchanges/0190a1c0-0000-7000-8000-00000000e000', method: 'GET' } },
  },
  interlocutor: {
    kind: 'person',
    id: '0190a1c0-0000-7000-8000-00000000a000',
    name: 'Ada Lovelace',
    presence: 'present',
  },
})

/** The box the route gives each to-do: a feature never imports another. */
const theBoxOf = (toDo: CalendarToDo) => (
  <input type="checkbox" aria-label={`La case de ${toDo.text}`} defaultChecked={Boolean(toDo.doneAt)} />
)

async function showTheCalendar(view: View, calendar: Partial<Calendar>) {
  const reviseToDo = vi.fn()
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(calendarQuery(periodOf(view, TODAY)).queryKey, {
    items: [],
    toDos: [],
    late: [],
    _links: CALENDAR_LINKS,
    ...calendar,
  })
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <CalendarPage
        view={view}
        day={TODAY}
        onShow={vi.fn()}
        contactSummary={() => null}
        interlocutorSearch={() => null}
        toDoBox={theBoxOf}
        reviseToDo={reviseToDo}
      />
    </QueryClientProvider>,
  )
  return reviseToDo
}

/** What a browser carries from the to-do dragged to where it is dropped. */
function aDrag() {
  const carried = new Map<string, string>()
  return {
    get types() {
      return [...carried.keys()]
    },
    setData: (type: string, value: string) => carried.set(type, value),
    getData: (type: string) => carried.get(type) ?? '',
  }
}

beforeEach(() => {
  vi.useFakeTimers({ toFake: ['Date'] })
  vi.setSystemTime(NOW)
  theApi.mockImplementation(async () => Response.json({}))
})

afterEach(() => {
  vi.useRealTimers()
  theApi.mockReset()
})

describe('the to-dos of my calendar', () => {
  it('stand in the band of their day without an hour, at their hour with one', async () => {
    await showTheCalendar('week', {
      toDos: [
        aToDo('Envoyer mon CV', '2026-11-14'),
        aToDo('Appeler la RH', '2026-11-14', { dueAt: '10:00' }),
      ],
    })

    const band = screen.getByRole('list', { name: 'Toute la journée du samedi 14 novembre 2026' })
    expect(within(band).getByText('Envoyer mon CV')).toBeInTheDocument()
    expect(within(band).queryByText('Appeler la RH')).not.toBeInTheDocument()
    const hours = screen.getByRole('list', { name: 'samedi 14 novembre 2026' })
    expect(within(hours).getByText('Appeler la RH')).toBeInTheDocument()
    expect(within(hours).getByText('10h00')).toBeInTheDocument()
  })

  it('stay done, greyed, with the box the page gives them', async () => {
    await showTheCalendar('month', {
      toDos: [aToDo('Envoyer mon CV', '2026-11-12', { doneAt: '2026-11-12T09:00:00+01:00' })],
    })

    expect(screen.getByRole('checkbox', { name: 'La case de Envoyer mon CV' })).toBeChecked()
    expect(screen.getByText('Envoyer mon CV')).toHaveClass('line-through')
    expect(screen.getByText('Envoyer mon CV').closest('[draggable]')).toHaveClass('bg-muted')
  })

  it('flag what is late by an icon and its words, never the colour alone', async () => {
    const late = aToDo('Envoyer mon CV', '2026-11-12')
    const call = aPlannedCall(new Date(2026, 10, 12, 14, 0))
    await showTheCalendar('agenda', {
      toDos: [late, aToDo('Relancer', '2026-11-14')],
      items: [call],
      late: [late.id, call.exchange.id],
    })

    expect(screen.getByText('Envoyer mon CV').closest('[data-late]')).toHaveTextContent('En retard')
    expect(screen.getByText('Relancer').closest('[data-late]')).toBeNull()
    expect(screen.getByRole('button', { name: '14h00, Appel avec Ada Lovelace, En retard' })).toHaveAttribute(
      'data-late',
    )
  })

  it('pulse once only, and not at all when the motion is reduced', async () => {
    const late = aToDo('Envoyer mon CV', '2026-11-12')
    await showTheCalendar('month', { toDos: [late], late: [late.id] })

    expect(screen.getByText('Envoyer mon CV').closest('[data-late]')).toHaveClass(
      'motion-safe:animate-late-once',
    )
  })

  it('are dropped on another day, their hour kept', async () => {
    const toDo = aToDo('Appeler la RH', '2026-11-14', { dueAt: '10:00' })
    const reviseToDo = await showTheCalendar('month', { toDos: [toDo] })
    const drag = aDrag()

    fireEvent.dragStart(screen.getByText('Appeler la RH'), { dataTransfer: drag })
    fireEvent.dragOver(screen.getByRole('listitem', { name: 'lundi 16 novembre 2026' }), {
      dataTransfer: drag,
    })
    fireEvent.drop(screen.getByRole('listitem', { name: 'lundi 16 novembre 2026' }), { dataTransfer: drag })

    expect(reviseToDo).toHaveBeenCalledWith(toDo.id, {
      text: 'Appeler la RH',
      dueOn: '2026-11-16',
      dueAt: '10:00',
    })
  })

  it('stay on their day when dropped there', async () => {
    const toDo = aToDo('Appeler la RH', '2026-11-14')
    const reviseToDo = await showTheCalendar('month', { toDos: [toDo] })
    const drag = aDrag()

    fireEvent.dragStart(screen.getByText('Appeler la RH'), { dataTransfer: drag })
    fireEvent.drop(screen.getByRole('listitem', { name: 'samedi 14 novembre 2026' }), { dataTransfer: drag })

    expect(reviseToDo).not.toHaveBeenCalled()
  })

  it('show the exchanges, the to-dos or both, as filtered', async () => {
    await showTheCalendar('agenda', {
      toDos: [aToDo('Envoyer mon CV', '2026-11-13')],
      items: [aPlannedCall(new Date(2026, 10, 16, 14, 0))],
    })

    await userEvent.click(screen.getByRole('button', { name: 'Échanges' }))
    expect([screen.queryByText('Envoyer mon CV'), screen.queryByText('Ada Lovelace')]).toEqual([
      null,
      expect.anything(),
    ])
    await userEvent.click(screen.getByRole('button', { name: 'À faire' }))
    expect([screen.queryByText('Envoyer mon CV'), screen.queryByText('Ada Lovelace')]).toEqual([
      expect.anything(),
      null,
    ])
    await userEvent.click(screen.getByRole('button', { name: 'Tout' }))
    expect(screen.getByText('Envoyer mon CV')).toBeInTheDocument()
    expect(screen.getByText('Ada Lovelace')).toBeInTheDocument()
  })
})
