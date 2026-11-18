import { fireEvent, screen } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { CalendarToDo } from '@/features/exchanges/calendar/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** A to-do dropped on another day of the calendar, on the routes of the SPA: no form tells its refusal, a toast does. */

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

const TO_DO = '0190a1c0-0000-7000-8000-0000000070d0'

const toDo: CalendarToDo = {
  id: TO_DO,
  text: 'Appeler la RH',
  dueOn: '2026-11-14',
  dueAt: null,
  doneAt: null,
  heldBy: { name: { firstName: 'Ada', lastName: 'Lovelace' } },
  contact: null,
  _links: {
    self: { href: `/api/to-dos/${TO_DO}`, method: 'GET' },
    revise: { href: `/api/to-dos/${TO_DO}`, method: 'PATCH' },
  },
}

const refusedForMyRole = {
  title: 'Forbidden',
  status: 403,
  detail: 'Your role does not allow this action.',
  reason: 'roleNotAllowed',
}

/** The api: my calendar holding the to-do, its revision refused for my role. */
function theApiRefusesTheRevisionForMyRole() {
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const { pathname } = new URL(request.url)
    const self = { self: { href: pathname, method: 'GET' } }
    if (request.method === 'PATCH') {
      return Response.json(refusedForMyRole, {
        status: 403,
        headers: { 'Content-Type': 'application/problem+json' },
      })
    }
    const answers: Record<string, unknown> = {
      '/api/me': me,
      '/api/me/connections': { connections: [], _links: self },
      '/api/me/calendar': { items: [], toDos: [toDo], late: [], _links: self },
    }
    return Response.json(answers[pathname] ?? { items: [], toDos: [], contactsToAttach: [], _links: self })
  })
}

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

describe('a to-do dropped on another day of the calendar', () => {
  afterEach(() => theApi.mockReset())

  it('tells in French a revision refused for my role', async () => {
    theApiRefusesTheRevisionForMyRole()
    await renderTheSpaAt('/calendar?view=month&date=2026-11-16')
    const drag = aDrag()

    fireEvent.dragStart(await screen.findByText('Appeler la RH'), { dataTransfer: drag })
    const monday = screen.getByRole('listitem', { name: 'lundi 16 novembre 2026' })
    fireEvent.dragOver(monday, { dataTransfer: drag })
    fireEvent.drop(monday, { dataTransfer: drag })

    expect(await screen.findByText('Votre rôle ne vous permet pas cette action.')).toBeInTheDocument()
  })
})
