import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** The calendar on the routes of the SPA: its view and its day in the URL, which the back button goes back through. */

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

const me: Account = {
  name: { firstName: 'Ada', lastName: 'Lovelace' },
  contactDetails: { email: null, phoneNumber: null, language: null },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

const A_DAY = '2026-11-16'

/** The api: my account, an empty calendar for any period. */
function theApiAnswersAnEmptyCalendar() {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL(input instanceof Request ? input.url : String(input))
    return Response.json(
      pathname === '/api/me' ? me : { items: [], _links: { self: { href: pathname, method: 'GET' } } },
    )
  })
}

describe('the calendar in the URL', () => {
  beforeEach(theApiAnswersAnEmptyCalendar)

  afterEach(() => {
    theApi.mockReset()
  })

  it('writes the view chosen by its key in the URL, the back button returning to the former one', async () => {
    const router = await renderTheSpaAt(`/calendar?view=day&date=${A_DAY}`)
    await screen.findByRole('heading', { name: 'Calendrier' })

    await userEvent.keyboard('m')

    await vi.waitFor(() => expect(router.state.location.search).toEqual({ view: 'month', date: A_DAY }))
    router.history.back()
    await vi.waitFor(() => expect(router.state.location.search).toEqual({ view: 'day', date: A_DAY }))
  })
})
