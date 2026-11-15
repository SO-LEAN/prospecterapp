import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import { renderTheSpaAt } from '@/test/render-the-spa'

/**
 * The layout every page of a member signed in goes through (`routes/_app.tsx`), on the routes of the SPA: what it shows
 * while their profile is being prepared, once it is, and when their account cannot be read.
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
  },
}))

const link = (href: string) => ({ href, method: 'PATCH' })

const myEmptyProfile: Account = {
  name: { firstName: null, lastName: null },
  contactDetails: { email: null, phoneNumber: null, language: null },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: {
    self: { href: '/api/me', method: 'GET' },
    changeMyName: link('/api/me/name'),
    changeMyContactDetails: link('/api/me/contact-details'),
  },
}

const myNamedProfile: Account = { ...myEmptyProfile, name: { firstName: 'Ada', lastName: 'Lovelace' } }

const json = (body: unknown, status: number) =>
  new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })

const refused = (status: number, reason: string) =>
  json({ type: 'about:blank', title: 'Refused', status, detail: `Refused: ${reason}`, reason }, status)

type Answer = () => Response

const anEmptyDay = () =>
  Promise.resolve(
    json({ today: [], next: [], _links: { self: { href: '/api/me/day', method: 'GET' } } }, 200),
  )
const aDayNeverRead = () => new Promise<Response>(() => {})
let myDay: () => Promise<Response> = anEmptyDay

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

/**
 * The api: my account answered in turn by `myAccount`, the last one kept, named once my name is given; what I sign
 * in with; my contact details given; an empty dashboard, an empty day and nothing to process; nothing else.
 */
function theApiAnswers(...myAccount: Answer[]) {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL(input instanceof Request ? input.url : String(input))
    if (pathname === '/api/me/name') {
      myAccount.splice(0, myAccount.length, myProfileNamed)
      return myProfileNamed()
    }
    if (pathname === '/api/me') {
      const answer = myAccount.length > 1 ? myAccount.shift() : myAccount[0]
      return answer ? answer() : refused(404, 'unknownMember')
    }
    if (pathname === '/api/me/sign-in') {
      return json({ loginEmail: 'ada@babbage.test' }, 200)
    }
    if (pathname === '/api/me/contact-details') {
      return json(myEmptyProfile, 200)
    }
    if (pathname === '/api/dashboard') {
      return json(anEmptyDashboard, 200)
    }
    if (pathname === '/api/organizations') {
      return json({ items: [], _links: {} }, 200)
    }
    if (pathname === '/api/me/day') {
      return myDay()
    }
    if (pathname === '/api/me/exchanges-to-process') {
      return json({ items: [], _links: { self: { href: pathname, method: 'GET' } } }, 200)
    }
    return refused(404, 'notFound')
  })
}

/** Signs in on the home page. */
async function signIn() {
  await renderTheSpaAt('/')
}

const myProfileNotCreatedYet = () => refused(404, 'unknownPerson')
const myProfileCreated = () => json(myEmptyProfile, 200)
const myProfileNamed = () => json(myNamedProfile, 200)

describe('the layout of a member signed in', () => {
  beforeEach(() => {
    theApi.mockReset()
    myDay = anEmptyDay
  })

  it('tells a member whose profile is not created yet that it is being prepared, and lets them sign out', async () => {
    theApiAnswers(myProfileNotCreatedYet)
    await signIn()

    expect(await screen.findByRole('status')).toHaveTextContent('Votre profil est en cours de préparation…')
    expect(screen.getByRole('button', { name: 'Se déconnecter' })).toBeInTheDocument()
    expect(screen.queryByText('Une erreur est survenue')).not.toBeInTheDocument()
  })

  it('asks their name, without a reload, once their profile is created', async () => {
    theApiAnswers(myProfileNotCreatedYet, myProfileCreated)
    await signIn()
    await screen.findByRole('status')

    expect(
      await screen.findByRole('heading', { name: 'Complétez votre profil' }, { timeout: 3_000 }),
    ).toBeInTheDocument()
    expect(screen.getByLabelText('Prénom')).toBeInTheDocument()
  })

  it('shows any other failure to read the account as an error, offering to sign out', async () => {
    theApiAnswers(() => refused(404, 'unknownMember'))
    await signIn()

    expect(await screen.findByText('Une erreur est survenue')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Réessayer' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Se déconnecter' })).toBeInTheDocument()
  })

  it('greets a member by their first name, the menu of their name offering their configuration', async () => {
    theApiAnswers(myProfileNamed)
    await signIn()

    expect(await screen.findByRole('heading', { name: 'Bonjour Ada' })).toBeInTheDocument()
    await userEvent.click(screen.getByRole('button', { name: 'Ada Lovelace, menu du compte' }))
    expect(await screen.findByRole('menuitem', { name: 'Configuration' })).toHaveAttribute(
      'href',
      '/settings',
    )
    expect(screen.getByRole('menuitem', { name: 'Se déconnecter' })).toBeInTheDocument()
  })

  it('greets a member while their day is still being read', async () => {
    myDay = aDayNeverRead
    theApiAnswers(myProfileNamed)
    await signIn()

    expect(await screen.findByRole('heading', { name: 'Bonjour Ada' })).toBeInTheDocument()
    expect(screen.queryByText('Aujourd’hui')).not.toBeInTheDocument()
  })

  it('leads by the main navigation to the dashboard, the organizations, the contacts and the calendar', async () => {
    theApiAnswers(myProfileNamed)
    await signIn()

    const mainNavigation = await screen.findByRole('navigation', { name: 'Navigation principale' })
    const linkTo = (name: string) => within(mainNavigation).getByRole('link', { name })
    expect(linkTo('Tableau de bord')).toHaveAttribute('href', '/')
    expect(linkTo('Organisations')).toHaveAttribute('href', '/organizations')
    expect(linkTo('Contacts')).toHaveAttribute('href', '/contacts')
    expect(linkTo('Calendrier')).toHaveAttribute('href', '/calendar')
  })

  it('leads a member who completed their profile to the home page, greeted by their first name', async () => {
    theApiAnswers(myProfileCreated)
    const router = await renderTheSpaAt('/calendar')

    await userEvent.type(await screen.findByLabelText('Prénom'), 'Ada')
    await userEvent.type(screen.getByLabelText('Nom'), 'Lovelace')
    await userEvent.click(screen.getByRole('button', { name: 'Continuer' }))

    expect(await screen.findByRole('heading', { name: 'Bonjour Ada' })).toBeInTheDocument()
    expect(router.state.location.pathname).toBe('/')
  })
})
