import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, cleanup, screen, within } from '@testing-library/react'
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
  name: string | null = 'Ada Lovelace',
  interlocutor: Partial<CalendarExchange['interlocutor']> = {},
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
    interlocutor: {
      kind: 'person',
      id: '0190a1c0-0000-7000-8000-00000000a000',
      name,
      presence: 'present',
      ...interlocutor,
    },
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

  it('says when the agenda holds no exchange', async () => {
    await showTheCalendar('agenda', TODAY)

    expect(screen.getByText('Aucun échange sur cette période.')).toBeInTheDocument()
  })

  it('lists the days holding exchanges in the agenda, today said', async () => {
    await showTheCalendar('agenda', TODAY, [
      anExchange(new Date(2026, 10, 13, 16, 0)),
      anExchange(new Date(2026, 10, 17, 9, 0), { channel: 'call' }),
    ])

    const [today, tuesday] = screen.getAllByRole('heading', { level: 2 })
    expect(today).toHaveTextContent('vendredi 13 novembreAujourd’hui')
    expect(tuesday).toHaveTextContent('mardi 17 novembre')
    expect(screen.getByRole('link', { name: '9h00, Appel avec Ada Lovelace, À venir' })).toBeInTheDocument()
  })

  it('titles each view by its period', async () => {
    const titleOf = async (view: View) => {
      await showTheCalendar(view, TODAY)
      const title = screen.getByRole('button', { name: /Aller à une date/ }).textContent
      cleanup()
      return title
    }

    expect(await titleOf('month')).toBe('novembre 2026Aller à une date')
    expect(await titleOf('agenda')).toBe('novembre 2026Aller à une date')
    expect(await titleOf('week')).toMatch(/^9\s?–\s?15 nov\. 2026Aller à une date$/)
    expect(await titleOf('day')).toBe('vendredi 13 novembre 2026Aller à une date')
  })

  it('shows the view pressed, goes to another, to today and to the previous period', async () => {
    const onShow = await showTheCalendar('month', parseDay('2026-12-20'))

    expect(screen.getByRole('button', { name: 'Mois' })).toHaveAttribute('aria-pressed', 'true')
    expect(screen.getByRole('button', { name: 'Agenda' })).toHaveAttribute('aria-pressed', 'false')
    await userEvent.click(screen.getByRole('button', { name: 'Agenda' }))
    await userEvent.click(screen.getByRole('button', { name: 'Aujourd’hui' }))
    await userEvent.click(screen.getByRole('button', { name: 'Précédent' }))

    expect(onShow.mock.calls).toEqual([
      ['agenda', parseDay('2026-12-20')],
      ['month', TODAY],
      ['month', parseDay('2026-11-01')],
    ])
  })

  it('reads the previous period as soon as its arrow has the focus', async () => {
    await showTheCalendar('week', TODAY)

    act(() => screen.getByRole('button', { name: 'Précédent' }).focus())

    await vi.waitFor(() => expect(periodsAsked()).toEqual(['2026-11-02']))
  })

  it('jumps to a day picked in a small month, browsed apart', async () => {
    const onShow = await showTheCalendar('week', TODAY)

    await userEvent.click(screen.getByRole('button', { name: /Aller à une date/ }))
    const small = screen.getByRole('dialog')
    expect(within(small).getByRole('button', { name: 'vendredi 13 novembre 2026' })).toHaveAttribute(
      'aria-pressed',
      'true',
    )
    await userEvent.click(within(small).getByRole('button', { name: 'Suivant' }))
    expect(within(small).getByText('décembre 2026')).toBeInTheDocument()
    await userEvent.click(within(small).getByRole('button', { name: 'Précédent' }))
    await userEvent.click(within(small).getByRole('button', { name: 'Précédent' }))
    await userEvent.click(within(small).getByRole('button', { name: 'mardi 6 octobre 2026' }))

    expect(onShow).toHaveBeenCalledWith('week', parseDay('2026-10-06'))
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
  })

  it('fades the days of the months around, marks today, opens a day by its number', async () => {
    const onShow = await showTheCalendar(
      'month',
      TODAY,
      [1, 2, 3].map((hour) => anExchange(new Date(2026, 10, 17, hour))),
    )

    expect(screen.getByRole('listitem', { name: 'lundi 26 octobre 2026' })).toHaveClass(
      'text-muted-foreground',
    )
    expect(screen.getByRole('listitem', { name: 'lundi 2 novembre 2026' })).not.toHaveClass(
      'text-muted-foreground',
    )
    const tuesday = screen.getByRole('listitem', { name: 'mardi 17 novembre 2026' })
    expect(within(tuesday).queryByRole('button', { name: /de plus/ })).not.toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'vendredi 13 novembre 2026' })).toHaveClass('bg-primary')
    expect(within(tuesday).getByRole('button', { name: 'mardi 17 novembre 2026' })).not.toHaveClass(
      'bg-primary',
    )
    expect(screen.getAllByText('lun.')).toHaveLength(1)
    await userEvent.click(within(tuesday).getByRole('button', { name: 'mardi 17 novembre 2026' }))

    expect(onShow).toHaveBeenCalledWith('day', parseDay('2026-11-17'))
  })

  it('opens a day of the week by its header, not the day shown', async () => {
    const onShow = await showTheCalendar('week', TODAY)

    await userEvent.click(screen.getByRole('button', { name: 'mardi 10 novembre 2026' }))
    expect(onShow).toHaveBeenCalledWith('day', parseDay('2026-11-10'))
    expect(screen.getByRole('button', { name: 'vendredi 13 novembre 2026' })).toHaveClass('text-primary')
    expect(screen.getByRole('button', { name: 'mardi 10 novembre 2026' })).not.toHaveClass('text-primary')
    cleanup()

    await showTheCalendar('day', TODAY)
    expect(screen.getByRole('button', { name: 'vendredi 13 novembre 2026' })).toBeDisabled()
  })

  it('writes the hours of the grid from 1h, the line of now at its hour', async () => {
    await showTheCalendar('day', TODAY)

    const hours = within(screen.getByRole('list', { name: 'Heures' })).getAllByRole('listitem')
    expect(hours).toHaveLength(24)
    expect(hours.map((hour) => hour.textContent).slice(0, 3)).toEqual(['', '1h', '2h'])
    expect(screen.getByLabelText('Maintenant')).toHaveStyle({ '--now': '10' })
  })

  it('leads an exchange with an organization to its card, one with a forgotten contact nowhere', async () => {
    await showTheCalendar('week', TODAY, [
      anExchange(new Date(2026, 10, 10, 9, 0), {}, 'ACME', {
        kind: 'organization',
        id: 'acme',
        presence: null,
      }),
      anExchange(new Date(2026, 10, 11, 9, 0), { status: 'done' }, null, { presence: 'forgotten' }),
    ])

    expect(screen.getByRole('link', { name: /Visio avec ACME/ })).toHaveAttribute(
      'href',
      '/organizations/acme',
    )
    expect(
      screen.getByRole('note', { name: '9h00, Visio avec Interlocuteur inconnu, Fait' }),
    ).toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /Interlocuteur inconnu/ })).not.toBeInTheDocument()
  })

  it('makes a planned exchange to handle once its minute passed', async () => {
    vi.useFakeTimers({ toFake: ['Date', 'setInterval', 'clearInterval'] })
    vi.setSystemTime(new Date(2026, 10, 13, 9, 59, 30))
    await showTheCalendar('day', TODAY, [anExchange(new Date(2026, 10, 13, 10, 0))])
    expect(screen.getByRole('link', { name: /À venir/ })).toBeInTheDocument()

    act(() => vi.advanceTimersByTime(60_000))

    expect(screen.getByRole('link', { name: /À traiter/ })).toBeInTheDocument()
  })
})
