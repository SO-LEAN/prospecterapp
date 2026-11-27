import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, screen } from '@testing-library/react'
import { toast } from 'sonner'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { Toaster } from '@/shared/ui/sonner'
import { renderInARouter } from '@/test/render-in-a-router'
import type { CalendarExchange } from '../calendar/queries'
import { NewBookings, READ_AGAIN_MS } from './new-bookings'

/** The application, while open, tells me of a booking made through my public link at my agenda's next reading. */

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

vi.mock('@/shared/lib/time-zone', () => ({ readerTimeZone: () => 'Europe/Paris' }))

const ADA = '0190a1c0-0000-7000-8000-00000000a000'
let lastId = 0
const nextId = () => `0190a1c0-0000-7000-8000-${String(++lastId).padStart(12, '0')}`

/** An upcoming exchange with Ada Lovelace, on Tuesday 24 November at 9:00 on the browser's clock, booked through my link or not. */
function anExchange(
  bookedByLink: boolean,
  channel: CalendarExchange['exchange']['channel'] = 'videoCall',
): CalendarExchange {
  const id = nextId()
  return {
    exchange: {
      id,
      channel,
      direction: 'outbound',
      occurredAt: new Date(2026, 10, 24, 9, 0).toISOString(),
      status: 'planned',
      durationInMinutes: 15,
      textFlagged: false,
      textToRedact: false,
      toRedact: false,
      organizationIds: [],
      attachmentCount: 0,
      contactToAttach: true,
      bookedByLink,
      createdByAnAI: false,
      _links: { self: { href: `/api/exchanges/${id}`, method: 'GET' } },
    },
    interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
  }
}

let myAgenda: CalendarExchange[] = []

function theApiAnswersMyAgenda() {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL((input as Request).url)
    return pathname === '/api/me/calendar'
      ? Response.json({
          items: myAgenda,
          toDos: [],
          late: [],
          _links: { self: { href: pathname, method: 'GET' } },
        })
      : new Response(null, { status: 404 })
  })
}

async function openTheApplicationWith(agenda: CalendarExchange[]) {
  myAgenda = agenda
  theApiAnswersMyAgenda()
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <NewBookings />
      <Toaster />
    </QueryClientProvider>,
  )
  await act(() => vi.advanceTimersByTimeAsync(0))
}

const myAgendaIsReadAgainWith = (agenda: CalendarExchange[]) => {
  myAgenda = agenda
  return act(() => vi.advanceTimersByTimeAsync(READ_AGAIN_MS))
}

const THE_TOAST = 'Nouvelle réservation : Visio avec Ada Lovelace, mar. 24 nov. · 9h00'

beforeEach(() => {
  vi.useFakeTimers({ shouldAdvanceTime: true })
})

afterEach(() => {
  act(() => toast.dismiss())
  vi.useRealTimers()
  theApi.mockReset()
})

describe('the bookings made by my public link', () => {
  it('are told at the next reading of my agenda', async () => {
    await openTheApplicationWith([])

    await myAgendaIsReadAgainWith([anExchange(true)])

    expect(await screen.findByText(THE_TOAST)).toBeInTheDocument()
  })

  it('are told once, the agenda read again', async () => {
    await openTheApplicationWith([])
    const booked = anExchange(true)
    await myAgendaIsReadAgainWith([booked])
    await screen.findByText(THE_TOAST)
    act(() => toast.dismiss())

    await myAgendaIsReadAgainWith([booked])

    expect(screen.queryByText(THE_TOAST)).not.toBeInTheDocument()
  })

  it('are not told when they were there as the application opened', async () => {
    await openTheApplicationWith([anExchange(true)])

    await myAgendaIsReadAgainWith(myAgenda)

    expect(screen.queryByText(THE_TOAST)).not.toBeInTheDocument()
  })

  it('leave out an exchange I planned myself', async () => {
    await openTheApplicationWith([])

    await myAgendaIsReadAgainWith([anExchange(false)])

    expect(screen.queryByText(/^Nouvelle réservation/)).not.toBeInTheDocument()
  })
})
