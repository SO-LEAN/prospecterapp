import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { toast } from 'sonner'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { Toaster } from '@/shared/ui/sonner'
import { renderInARouter } from '@/test/render-in-a-router'
import { startOfDay } from '../../calendar/dates'
import { type DayExchange, type MyDay, myDayQuery } from '../../my-day/queries'
import type { ExchangeDetail } from '../../queries'
import { ExchangeReminders } from './exchange-reminders'

/** Being reminded of an exchange I planned, the application open, on a clock the test moves. */

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

const ADA = '0190a1c0-0000-7000-8000-00000000a000'
const ACME = '0190a1c0-0000-7000-8000-00000000ac00'
const TODAY = new Date(2026, 10, 13)
const at = (hours: number, minutes: number) => new Date(2026, 10, 13, hours, minutes)
const TEN_MINUTES_BEFORE = { enabled: true, minutesBefore: 10 }
const THE_REMINDER = 'Visio avec Ada Lovelace à 14h00'

let lastId = 0
const nextId = () => `0190a1c0-0000-7000-8000-${String(++lastId).padStart(12, '0')}`

/** An exchange planned with Ada Lovelace of ACME, as my day tells it. */
function planned(
  channel: DayExchange['exchange']['channel'],
  occurredAt: Date,
  exchange: Partial<DayExchange['exchange']> = {},
): DayExchange {
  const id = nextId()
  return {
    exchange: {
      id,
      channel,
      direction: 'outbound',
      occurredAt: occurredAt.toISOString(),
      status: 'planned',
      durationInMinutes: 30,
      textFlagged: false,
      textToMakeConform: false,
      toMakeConform: false,
      organizationIds: [ACME],
      attachmentCount: 0,
      _links: { self: { href: `/api/exchanges/${id}`, method: 'GET' } },
      ...exchange,
    },
    interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
    organization: {
      id: ACME,
      name: 'ACME',
      organization: { href: `/api/organizations/${ACME}`, method: 'GET' },
    },
  }
}

const aVideoCallAt14 = () =>
  planned('videoCall', at(14, 0), { link: 'https://meet.test/ada', durationInMinutes: 30 })

const aDayOf = (today: DayExchange[]): MyDay => ({
  today,
  next: [],
  _links: { self: { href: '/api/me/day', method: 'GET' } },
})

const theDetailOf = (item: DayExchange, detail: Partial<ExchangeDetail>): ExchangeDetail => ({
  ...item.exchange,
  interlocutor: item.interlocutor,
  canceller: null,
  text: null,
  noted: { at: TODAY.toISOString(), by: { name: { firstName: 'Michel', lastName: 'Maier' } } },
  corrected: null,
  madeConform: null,
  attachments: [],
  hiddenAttachments: 0,
  ...detail,
})

let myDay = aDayOf([])

/** My day as it is now, and the detail of each exchange, by its path; nothing else. */
function theApiAnswers(details: Record<string, ExchangeDetail> = {}) {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL((input as Request).url)
    const answer = pathname === '/api/me/day' ? myDay : details[pathname]
    return answer ? Response.json(answer) : new Response(null, { status: 404 })
  })
}

type Settings = { enabled: boolean; minutesBefore: number }

const TABS = ['a tab', 'another tab']

/** The application open on my day, in as many tabs as asked, sharing the storage of the browser. */
async function openTheApplication(today: DayExchange[], settings: Settings = TEN_MINUTES_BEFORE, tabs = 1) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY, retry: false } },
  })
  myDay = aDayOf(today)
  queryClient.setQueryData(myDayQuery(startOfDay(new Date())).queryKey, myDay)
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      {TABS.slice(0, tabs).map((tab) => (
        <ExchangeReminders key={tab} settings={settings} />
      ))}
      <Toaster />
    </QueryClientProvider>,
  )
  return {
    /** My day read again after a gesture on one of its exchanges. */
    readAgain: (changed: DayExchange[]) => {
      myDay = aDayOf(changed)
      act(() => queryClient.setQueryData(myDayQuery(startOfDay(new Date())).queryKey, myDay))
    },
  }
}

const itIs = (hours: number, minutes: number) =>
  act(() => vi.advanceTimersByTimeAsync(at(hours, minutes).getTime() - Date.now()))

const theTabIs = (state: DocumentVisibilityState) => {
  Object.defineProperty(document, 'visibilityState', { value: state, configurable: true })
  document.dispatchEvent(new Event('visibilitychange'))
}

/** The notifications of the browser, allowed: what each says, and its click. */
function theBrowserNotifies() {
  const shown: { title: string; body?: string; click: () => void }[] = []
  class Shown {
    static permission = 'granted'
    onclick: (() => void) | null = null
    constructor(title: string, options?: NotificationOptions) {
      shown.push({ title, body: options?.body, click: () => this.onclick?.() })
    }
    close() {}
  }
  vi.stubGlobal('Notification', Shown)
  return shown
}

beforeEach(() => {
  vi.useFakeTimers({ shouldAdvanceTime: true })
  vi.setSystemTime(at(13, 40))
  localStorage.clear()
  theApiAnswers()
})

afterEach(() => {
  act(() => toast.dismiss())
  theTabIs('visible')
  vi.useRealTimers()
  vi.restoreAllMocks()
  vi.unstubAllGlobals()
  theApi.mockReset()
})

describe('the reminders of my exchanges', () => {
  it('reminds of an exchange ten minutes before it, with its channel, contact, organization and hour', async () => {
    await openTheApplication([aVideoCallAt14()])

    await itIs(13, 49)
    expect(screen.queryByText(THE_REMINDER)).not.toBeInTheDocument()
    await itIs(13, 50)

    expect(await screen.findByText(THE_REMINDER)).toBeInTheDocument()
    expect(screen.getByText('ACME')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Rejoindre' })).toHaveAttribute('href', 'https://meet.test/ada')
  })

  it('stays until it is closed, and is not shown again', async () => {
    await openTheApplication([aVideoCallAt14()])
    await itIs(13, 50)
    await itIs(13, 58)
    expect(screen.getByText(THE_REMINDER)).toBeInTheDocument()

    await userEvent.click(screen.getByRole('button', { name: 'Fermer' }))
    await waitFor(() => expect(screen.queryByText(THE_REMINDER)).not.toBeInTheDocument())
    await itIs(13, 59)

    expect(screen.queryByText(THE_REMINDER)).not.toBeInTheDocument()
  })

  it('shows a reminder once, even with the application open in two tabs', async () => {
    const shown = vi.spyOn(toast, 'custom')
    await openTheApplication([aVideoCallAt14()], TEN_MINUTES_BEFORE, 2)

    await itIs(13, 50)

    expect(await screen.findByText(THE_REMINDER)).toBeInTheDocument()
    expect(shown).toHaveBeenCalledTimes(1)
  })

  it('reminds at once when the application opens after the moment of the reminder, before the exchange', async () => {
    vi.setSystemTime(at(13, 55))

    await openTheApplication([aVideoCallAt14()])

    expect(await screen.findByText(THE_REMINDER)).toBeInTheDocument()
  })

  it('reminds no more once the exchange started', async () => {
    vi.setSystemTime(at(14, 5))

    await openTheApplication([aVideoCallAt14()])

    expect(screen.queryByText(THE_REMINDER)).not.toBeInTheDocument()
  })

  it('moves the reminder of an exchange moved', async () => {
    const videoCall = aVideoCallAt14()
    const { readAgain } = await openTheApplication([videoCall])

    readAgain([{ ...videoCall, exchange: { ...videoCall.exchange, occurredAt: at(16, 0).toISOString() } }])
    await itIs(13, 50)
    expect(screen.queryByText(THE_REMINDER)).not.toBeInTheDocument()
    await itIs(15, 50)

    expect(await screen.findByText('Visio avec Ada Lovelace à 16h00')).toBeInTheDocument()
  })

  it('removes the reminder of an exchange cancelled', async () => {
    const videoCall = aVideoCallAt14()
    const { readAgain } = await openTheApplication([videoCall])

    readAgain([{ ...videoCall, exchange: { ...videoCall.exchange, status: 'cancelled' } }])
    await itIs(13, 50)

    expect(screen.queryByText(THE_REMINDER)).not.toBeInTheDocument()
  })

  it('reminds of nothing when my reminders are off', async () => {
    await openTheApplication([aVideoCallAt14()], { enabled: false, minutesBefore: 10 })

    await itIs(13, 50)

    expect(screen.queryByText(THE_REMINDER)).not.toBeInTheDocument()
  })

  it('reminds again five minutes later when asked to', async () => {
    await openTheApplication([aVideoCallAt14()])
    await itIs(13, 50)

    await userEvent.click(await screen.findByRole('button', { name: 'Dans 5 min' }))
    await waitFor(() => expect(screen.queryByText(THE_REMINDER)).not.toBeInTheDocument())
    await itIs(13, 54)
    expect(screen.queryByText(THE_REMINDER)).not.toBeInTheDocument()
    await itIs(13, 55)

    expect(await screen.findByText(THE_REMINDER)).toBeInTheDocument()
  })

  it('offers to call the interlocutor of a call, by the number its detail gives', async () => {
    const call = planned('call', at(14, 0))
    theApiAnswers({
      [`/api/exchanges/${call.exchange.id}`]: theDetailOf(call, { phoneNumber: '03 88 11 22 33' }),
    })
    await openTheApplication([call])

    await itIs(13, 50)

    expect(await screen.findByText('Appel avec Ada Lovelace à 14h00')).toBeInTheDocument()
    expect(await screen.findByRole('link', { name: 'Appeler' })).toHaveAttribute('href', 'tel:0388112233')
  })

  it('offers the route to the place of a meeting', async () => {
    await openTheApplication([planned('meeting', at(14, 0), { place: '1 rue de la Paix, Paris' })])

    await itIs(13, 50)

    expect(await screen.findByText('Rendez-vous avec Ada Lovelace à 14h00')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /Itinéraire/ })).toHaveAttribute(
      'href',
      expect.stringContaining('openstreetmap'),
    )
  })

  it('notifies by the browser, and makes the title of the tab blink, when the tab is not in front', async () => {
    const notifications = theBrowserNotifies()
    const title = document.title
    await openTheApplication([aVideoCallAt14()])
    theTabIs('hidden')

    await itIs(13, 50)
    await itIs(13, 51)

    expect(notifications).toEqual([expect.objectContaining({ title: THE_REMINDER, body: 'ACME' })])
    const titles = new Set<string>()
    for (let second = 0; second < 3; second++) {
      titles.add(document.title)
      await act(() => vi.advanceTimersByTimeAsync(1_000))
    }
    expect(titles).toEqual(new Set([title, '🔔 Visio dans 10 min']))
    theTabIs('visible')
    expect(document.title).toBe(title)
  })
})
