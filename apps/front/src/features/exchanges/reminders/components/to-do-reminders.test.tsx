import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { toast } from 'sonner'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { Toaster } from '@/shared/ui/sonner'
import { renderInARouter } from '@/test/render-in-a-router'
import { theMomentOfTheTest } from '@/test/the-moment-of-the-tests'
import {
  type ExchangesToProcess,
  exchangesToProcessQuery,
  type ToDoToProcess,
} from '../../to-process/queries'
import { ToDoReminders } from './to-do-reminders'

/** Reminders of my to-dos due today, while the application is open, on a clock that the test moves. */

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

const at = (hours: number, minutes: number) => new Date(2026, 10, 13, hours, minutes)
const TEN_MINUTES_BEFORE = { enabled: true, minutesBefore: 10 }

let lastId = 0
function aToDoOfTheDay(text: string, dueAt: string | null, daysOverdue = 0): ToDoToProcess {
  const id = `0190a1c0-0000-7000-8000-${String(++lastId).padStart(12, '0')}`
  return {
    toDo: {
      id,
      text,
      dueOn: '2026-11-13',
      dueAt,
      doneAt: null,
      heldBy: { name: { firstName: 'Grace', lastName: 'Hopper' } },
      contact: null,
      _links: { self: { href: `/api/to-dos/${id}`, method: 'GET' } },
    },
    daysOverdue,
  }
}

async function openTheApplication(toDos: ToDoToProcess[]) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY, retry: false } },
  })
  const toProcess: ExchangesToProcess = {
    items: [],
    toDos,
    contactsToAttach: [],
    _links: { self: { href: '/', method: 'GET' } },
  }
  queryClient.setQueryData(exchangesToProcessQuery().queryKey, toProcess)
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <ToDoReminders settings={TEN_MINUTES_BEFORE} />
      <Toaster />
    </QueryClientProvider>,
  )
}

const itIs = (hours: number, minutes: number) =>
  act(() => vi.advanceTimersByTimeAsync(at(hours, minutes).getTime() - theMomentOfTheTest().getTime()))

beforeEach(() => {
  vi.useFakeTimers({ shouldAdvanceTime: true })
  vi.setSystemTime(at(8, 0))
  localStorage.clear()
  theApi.mockImplementation(async () => new Response(null, { status: 404 }))
})

afterEach(() => {
  act(() => toast.dismiss())
  vi.useRealTimers()
  theApi.mockReset()
})

describe('the reminders of my to-dos', () => {
  it('remind a to-do with an hour at the delay of the exchanges', async () => {
    await openTheApplication([aToDoOfTheDay('Appeler la RH', '10:00')])

    await itIs(9, 49)
    expect(screen.queryByText(/^À faire à 10h00\s: Appeler la RH$/)).not.toBeInTheDocument()
    await itIs(9, 50)
    expect(await screen.findByText(/^À faire à 10h00\s: Appeler la RH$/)).toBeInTheDocument()
  })

  it('writes the hour of a to-do without a leading zero', async () => {
    await openTheApplication([aToDoOfTheDay('Appeler la RH', '08:30')])

    await itIs(8, 20)
    expect(await screen.findByText(/^À faire à 8h30\s: Appeler la RH$/)).toBeInTheDocument()
  })

  it('remind a to-do without an hour at 9:00 on its day, once', async () => {
    await openTheApplication([aToDoOfTheDay('Envoyer mon CV', null)])

    await itIs(9, 0)
    expect(await screen.findByText(/^À faire\s: Envoyer mon CV$/)).toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: 'Dans 5 min' }))
    await waitFor(() => expect(screen.queryByText(/^À faire\s: Envoyer mon CV$/)).not.toBeInTheDocument())
    await itIs(9, 5)
    expect(await screen.findByText(/^À faire\s: Envoyer mon CV$/)).toBeInTheDocument()
  })

  it('remind no to-do late', async () => {
    const late = aToDoOfTheDay('Relancer', null, 2)
    await openTheApplication([{ ...late, toDo: { ...late.toDo, dueOn: '2026-11-11' } }])

    await itIs(9, 0)
    expect(screen.queryByText(/Relancer/)).not.toBeInTheDocument()
  })
})
