import { act, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { toast } from 'sonner'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Contact } from '@/features/contacts/queries'
import type { DayExchange } from '@/features/exchanges/my-day/queries'
import type { ExchangeDetail } from '@/features/exchanges/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** A reminder of an exchange, on the routes of the SPA: whatever the page, a click opens the exchange on the card of its contact. */

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: {
    accessToken: async () => 'an access token',
    renewedAccessToken: async () => null,
    signOut: async () => {},
  },
}))

const ADA = '0190a1c0-0000-7000-8000-000000000001'
const VIDEO_CALL = '0190a1c0-0000-7000-8000-000000000020'
const AT_14 = new Date(2026, 10, 13, 14, 0)
const THE_REMINDER = 'Visio avec Ada Lovelace à 14h00'

const me: Account = {
  name: { firstName: 'Grace', lastName: 'Hopper' },
  contactDetails: { email: null, phoneNumber: null, language: null },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

const ada: Contact = {
  id: ADA,
  name: { firstName: 'Ada', lastName: 'Lovelace' },
  contactDetails: { email: 'ada@acme.test', phoneNumber: null, language: null },
  masked: false,
  _links: { self: { href: `/api/contacts/${ADA}`, method: 'GET' } },
}

const theVideoCall: DayExchange = {
  exchange: {
    id: VIDEO_CALL,
    channel: 'videoCall',
    direction: 'outbound',
    occurredAt: AT_14.toISOString(),
    status: 'planned',
    durationInMinutes: 30,
    link: 'https://meet.test/ada',
    textFlagged: false,
    textToMakeConform: false,
    toMakeConform: false,
    organizationIds: [],
    attachmentCount: 0,
    _links: { self: { href: `/api/exchanges/${VIDEO_CALL}`, method: 'GET' } },
  },
  interlocutor: { kind: 'person', id: ADA, name: 'Ada Lovelace', presence: 'present' },
  organization: null,
}

const itsDetail: ExchangeDetail = {
  ...theVideoCall.exchange,
  interlocutor: theVideoCall.interlocutor,
  canceller: null,
  text: 'Présenter la démonstration',
  noted: { at: AT_14.toISOString(), by: { name: { firstName: 'Grace', lastName: 'Hopper' } } },
  corrected: null,
  madeConform: null,
  attachments: [],
  hiddenAttachments: 0,
}

/** The api: my account, my reminders ten minutes before, my day with the video call, Ada and the detail of the call. */
function theApiAnswers() {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL((input as Request).url)
    const answers: Record<string, unknown> = {
      '/api/me': me,
      '/api/me/connections': {
        connections: [],
        _links: { self: { href: '/api/me/connections', method: 'GET' } },
      },
      '/api/me/reminders': {
        enabled: true,
        minutesBefore: 10,
        _links: { self: { href: '/api/me/reminders', method: 'GET' } },
      },
      '/api/me/day': {
        today: [theVideoCall],
        next: [],
        _links: { self: { href: '/api/me/day', method: 'GET' } },
      },
      [`/api/contacts/${ADA}`]: ada,
      [`/api/exchanges/${VIDEO_CALL}`]: itsDetail,
    }
    return Response.json(answers[pathname] ?? { items: [], _links: {} })
  })
}

/** The notifications of the browser, allowed: the click of each. */
function theBrowserNotifies() {
  const clicks: (() => void)[] = []
  class Shown {
    static permission = 'granted'
    onclick: (() => void) | null = null
    constructor() {
      clicks.push(() => this.onclick?.())
    }
    close() {}
  }
  vi.stubGlobal('Notification', Shown)
  return clicks
}

const theTabIs = (state: DocumentVisibilityState) =>
  Object.defineProperty(document, 'visibilityState', { value: state, configurable: true })

const theCardOfAdaOpensOnTheVideoCall = async (router: Awaited<ReturnType<typeof renderTheSpaAt>>) => {
  const detail = await screen.findByRole('dialog', { name: 'Échange' })
  expect(await within(detail).findByText('Présenter la démonstration')).toBeInTheDocument()
  expect(router.state.location.pathname).toBe(`/contacts/${ADA}`)
  expect(screen.getByRole('heading', { name: 'Ada Lovelace', hidden: true })).toBeInTheDocument()
}

describe('a reminder of an exchange in the application', () => {
  beforeEach(() => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    vi.setSystemTime(new Date(2026, 10, 13, 13, 50))
    localStorage.clear()
    theApiAnswers()
  })

  afterEach(() => {
    act(() => toast.dismiss())
    theTabIs('visible')
    vi.useRealTimers()
    vi.unstubAllGlobals()
    theApi.mockReset()
  })

  it('opens the exchange on the card of its contact, clicked from any page', async () => {
    const router = await renderTheSpaAt('/organizations')

    await userEvent.click(await screen.findByRole('button', { name: new RegExp(THE_REMINDER) }))

    await theCardOfAdaOpensOnTheVideoCall(router)
    await waitFor(() =>
      expect(screen.queryByText(THE_REMINDER, { ignore: '[role=dialog] *' })).not.toBeInTheDocument(),
    )
  })

  it('opens the exchange on the card of its contact from the notification of the browser', async () => {
    const clicks = theBrowserNotifies()
    theTabIs('hidden')
    const router = await renderTheSpaAt('/organizations')
    for (let second = 0; second < 5 && clicks.length === 0; second++) {
      await act(() => vi.advanceTimersByTimeAsync(1_000))
    }
    expect(clicks).toHaveLength(1)

    theTabIs('visible')
    act(() => clicks[0]?.())

    await theCardOfAdaOpensOnTheVideoCall(router)
  })
})
