import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import { dayOf, parseDay, periodOf, type View } from '../dates'
import { type CalendarExchange, calendarQuery } from '../queries'
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

let identity = 0
const anExchange = (
  occurredAt: Date,
  exchange: Partial<CalendarExchange['exchange']> = {},
  name = 'Ada Lovelace',
): CalendarExchange => {
  const id = `0190a1c0-0000-7000-8000-${String(++identity).padStart(12, '0')}`
  return {
    exchange: {
      id,
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
      _links: { self: { href: `/api/exchanges/${id}`, method: 'GET' } },
      ...exchange,
    },
    interlocutor: { kind: 'person', id: '0190a1c0-0000-7000-8000-00000000a000', name, presence: 'present' },
  }
}

/** The URLs the calendar asked the API for, its period in each: what it read ahead. */
const periodsAsked = () =>
  theApi.mock.calls.map(([request]) => {
    const url = new URL(request instanceof Request ? request.url : String(request))
    return dayOf(new Date(url.searchParams.get('from') ?? ''))
  })

async function showTheCalendar(view: View, day: Date, items: CalendarExchange[] = []) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(calendarQuery(periodOf(view, day)).queryKey, { items })
  const onShow = vi.fn()
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <CalendarPage view={view} day={day} onShow={onShow} />
    </QueryClientProvider>,
  )
  return onShow
}

beforeEach(() => {
  vi.useFakeTimers({ toFake: ['Date'] })
  vi.setSystemTime(NOW)
  theApi.mockImplementation(async () => Response.json({ items: [] }))
})

afterEach(() => {
  vi.useRealTimers()
  theApi.mockReset()
})

describe('CalendarPage', () => {
  it('shows each exchange on its day with its hour, its channel and who it is with, in the look of its status', async () => {
    await showTheCalendar('month', TODAY, [
      anExchange(new Date(2026, 10, 13, 9, 0)),
      anExchange(new Date(2026, 10, 13, 16, 0), { channel: 'call' }),
      anExchange(new Date(2026, 10, 3, 9, 0), { status: 'done', channel: 'call' }),
      anExchange(new Date(2026, 10, 20, 10, 0), { status: 'cancelled', channel: 'meeting' }),
    ])

    const today = screen.getByRole('listitem', { name: 'vendredi 13 novembre 2026' })
    expect(within(today).getByRole('link', { name: '9h00, Visio avec Ada Lovelace, À traiter' })).toHaveClass(
      'bg-warning',
    )
    expect(within(today).getByRole('link', { name: '16h00, Appel avec Ada Lovelace, À venir' })).toHaveClass(
      'bg-primary',
    )
    expect(
      within(screen.getByRole('listitem', { name: 'mardi 3 novembre 2026' })).getByRole('link', {
        name: /Fait/,
      }),
    ).toHaveClass('bg-muted')
    expect(
      within(screen.getByRole('listitem', { name: 'vendredi 20 novembre 2026' })).getByRole('link', {
        name: /Annulé/,
      }),
    ).toHaveClass('line-through')
  })

  it('shows three exchanges of a day in the month, then how many more, which opens the day', async () => {
    const day = new Date(2026, 10, 17)
    const onShow = await showTheCalendar(
      'month',
      TODAY,
      [9, 10, 11, 14, 15].map((hour) => anExchange(new Date(2026, 10, 17, hour, 0))),
    )

    const cell = screen.getByRole('listitem', { name: 'mardi 17 novembre 2026' })
    expect(within(cell).getAllByRole('link')).toHaveLength(3)
    await userEvent.click(
      within(cell).getByRole('button', { name: '2 de plus le mardi 17 novembre 2026, voir la journée' }),
    )

    expect(onShow).toHaveBeenCalledWith('day', day)
  })

  it('goes to the next period, read as soon as its arrow is hovered', async () => {
    const onShow = await showTheCalendar('month', TODAY)

    await userEvent.hover(screen.getByRole('button', { name: 'Suivant' }))
    await userEvent.click(screen.getByRole('button', { name: 'Suivant' }))

    expect(periodsAsked()).toEqual(['2026-11-30'])
    expect(onShow).toHaveBeenCalledWith('month', new Date(2026, 11, 1))
  })

  it('reads the three days before and after a day ahead', async () => {
    await showTheCalendar('day', TODAY)

    expect(periodsAsked().sort()).toEqual([
      '2026-11-10',
      '2026-11-11',
      '2026-11-12',
      '2026-11-14',
      '2026-11-15',
      '2026-11-16',
    ])
  })

  it('changes its view and its period by the keys', async () => {
    const onShow = await showTheCalendar('week', parseDay('2026-11-20'))

    await userEvent.keyboard('j')
    await userEvent.keyboard('{ArrowLeft}')
    await userEvent.keyboard('t')
    await userEvent.click(screen.getByRole('button', { name: /Aller à une date/ }))
    act(() => screen.getByRole('button', { name: 'mardi 24 novembre 2026' }).focus())
    await userEvent.keyboard('m')

    expect(onShow.mock.calls).toEqual([
      ['day', parseDay('2026-11-20')],
      ['week', parseDay('2026-11-13')],
      ['week', TODAY],
      ['month', parseDay('2026-11-20')],
    ])
  })

  it('places an exchange of the week at its hour, the line of now on today', async () => {
    await showTheCalendar('week', TODAY, [
      anExchange(new Date(2026, 10, 12, 14, 30), { durationInMinutes: 90 }),
    ])

    const thursday = screen.getByRole('list', { name: 'jeudi 12 novembre 2026' })
    expect(
      within(thursday)
        .getByRole('link', { name: /14h30, Visio avec Ada Lovelace/ })
        .closest('li'),
    ).toHaveStyle({
      '--start': '14.5',
      '--length': '1.5',
    })
    expect(
      within(screen.getByRole('list', { name: 'vendredi 13 novembre 2026' })).getByLabelText('Maintenant'),
    ).toBeVisible()
  })

  it('lists the days holding exchanges in the agenda, and says when there is none', async () => {
    await showTheCalendar('agenda', TODAY)

    expect(screen.getByText('Aucun échange sur cette période.')).toBeInTheDocument()
  })
})
