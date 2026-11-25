import { act, screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { toast } from 'sonner'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { ToDo } from '@/features/to-dos/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/**
 * A to-do ticked off or postponed at hand, on the SPA's routes: in « À traiter » on the dashboard, where it then
 * leaves, and in the calendar.
 */

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
    signOutEverywhere: async () => {},
  },
}))

const me: Account = {
  name: { firstName: 'Ada', lastName: 'Lovelace' },
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

const anEmptyDashboard = {
  organizationName: 'Babbage & Co',
  markers: [],
  stats: {
    activeOrganizations: 0,
    locatedOrganizations: 0,
    contacts: 0,
    exchanges: 0,
    exchangesThisMonth: 0,
  },
}

const TO_DO = '0190a1c0-0000-7000-8000-0000000070d0'
const TODAY = '2026-11-18'
const TOMORROW = '2026-11-19'
const self = (href: string) => ({ self: { href, method: 'GET' } })

/** The to-do as the API keeps it: due today, then done once ticked off, again to do once unticked. */
function theToDo() {
  let toDo: ToDo = {
    id: TO_DO,
    text: 'Envoyer le CV à Nicolas Bonnet',
    dueOn: TODAY,
    dueAt: null,
    doneAt: null,
    heldBy: { name: { firstName: 'Ada', lastName: 'Lovelace' } },
    contact: null,
    _links: {
      ...self(`/api/to-dos/${TO_DO}`),
      revise: { href: `/api/to-dos/${TO_DO}`, method: 'PATCH' },
      tickOff: { href: `/api/to-dos/${TO_DO}/done`, method: 'POST' },
    },
  }
  const toDoAgain = toDo

  return {
    get current() {
      return toDo
    },
    tickOff() {
      toDo = {
        ...toDo,
        doneAt: `${TODAY}T09:00:00+00:00`,
        _links: {
          ...self(`/api/to-dos/${TO_DO}`),
          untick: { href: `/api/to-dos/${TO_DO}/done`, method: 'DELETE' },
        },
      }
    },
    untick() {
      toDo = toDoAgain
    },
    revise(dueOn: string) {
      toDo = { ...toDo, dueOn }
    },
  }
}

/** The API keeps the to-do and serves it in what I have to process while it is due today and to do, and in my calendar. */
function theApiKeeps(toDo: ReturnType<typeof theToDo>) {
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const { pathname } = new URL(request.url)
    if (pathname === `/api/to-dos/${TO_DO}/done`) {
      request.method === 'POST' ? toDo.tickOff() : toDo.untick()
      return request.method === 'POST' ? Response.json(toDo.current) : new Response(null, { status: 204 })
    }
    if (pathname === `/api/to-dos/${TO_DO}` && request.method === 'PATCH') {
      toDo.revise(((await request.clone().json()) as { dueOn: string }).dueOn)
      return Response.json(toDo.current)
    }
    const toProcess =
      toDo.current.doneAt || toDo.current.dueOn !== TODAY ? [] : [{ toDo: toDo.current, daysOverdue: 0 }]
    const answers: Record<string, unknown> = {
      '/api/me': me,
      '/api/dashboard': anEmptyDashboard,
      '/api/organizations': { items: [], _links: self(pathname) },
      '/api/me/day': { today: [], next: [], _links: self(pathname) },
      '/api/me/exchanges-to-process': {
        items: [],
        toDos: toProcess,
        contactsToAttach: [],
        _links: self(pathname),
      },
      '/api/me/connections': { connections: [], _links: self(pathname) },
      '/api/me/calendar': { items: [], toDos: [toDo.current], late: [], _links: self(pathname) },
    }
    return Response.json(answers[pathname] ?? { _links: self(pathname) })
  })
}

const sent = (...methods: string[]) =>
  theApi.mock.calls
    .map(([request]) => request as Request)
    .filter(({ method }) => methods.includes(method))
    .map(({ method, url }) => [method, new URL(url).pathname])

const toProcess = () => screen.queryByRole('region', { name: 'Actions à faire' })

const theToast = async () =>
  (await screen.findByText('Action faite')).closest('[data-sonner-toast]') as HTMLElement

/** Sonner removes a closed toast after its own timer. Once the test is over, that timer would find no window. */
afterEach(async () => {
  act(() => {
    toast.dismiss()
  })
  await waitFor(() => expect(document.querySelector('[data-sonner-toast]')).toBeNull())
  theApi.mockReset()
})

describe('a to-do in « À traiter »', () => {
  it('leaves once ticked off, and comes back when its toast takes the tick back', async () => {
    theApiKeeps(theToDo())
    await renderTheSpaAt('/')

    await userEvent.click(await screen.findByRole('checkbox', { name: /^Cocher/ }))
    await waitFor(() => expect(toProcess()).not.toBeInTheDocument())
    await userEvent.click(within(await theToast()).getByRole('button', { name: 'Annuler' }))

    expect(await screen.findByText('Envoyer le CV à Nicolas Bonnet')).toBeInTheDocument()
    expect(sent('POST', 'DELETE')).toEqual([
      ['POST', `/api/to-dos/${TO_DO}/done`],
      ['DELETE', `/api/to-dos/${TO_DO}/done`],
    ])
  })

  it('leaves once postponed to tomorrow', async () => {
    theApiKeeps(theToDo())
    await renderTheSpaAt('/')

    await userEvent.click(await screen.findByRole('button', { name: 'Reporter' }))
    const revision = await screen.findByRole('dialog')
    await userEvent.click(within(revision).getByRole('button', { name: 'Demain' }))
    await userEvent.click(within(revision).getByRole('button', { name: 'Enregistrer' }))

    await waitFor(() => expect(toProcess()).not.toBeInTheDocument())
    expect(sent('POST', 'PATCH', 'DELETE')).toEqual([['PATCH', `/api/to-dos/${TO_DO}`]])
    const [request] = theApi.mock.calls
      .map(([call]) => call as Request)
      .filter(({ method }) => method === 'PATCH')
    expect(await request?.json()).toEqual({
      text: 'Envoyer le CV à Nicolas Bonnet',
      dueOn: TOMORROW,
      dueAt: null,
    })
  })
})

describe('a to-do in the calendar', () => {
  it('offers to take its tick back', async () => {
    theApiKeeps(theToDo())
    await renderTheSpaAt(`/calendar?view=week&date=${TODAY}`)

    await userEvent.click(await screen.findByRole('checkbox', { name: /^Cocher/ }))

    expect(within(await theToast()).getByRole('button', { name: 'Annuler' })).toBeInTheDocument()
  })
})
