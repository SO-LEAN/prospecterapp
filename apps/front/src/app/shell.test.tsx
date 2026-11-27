import { screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import { usePreferences } from '@/shared/stores/preferences'
import { renderTheSpaAt } from '@/test/render-the-spa'

/**
 * The shell around a signed-in member's pages, on the SPA's routes: the ink sidebar with the brand, my account and
 * the navigation, and the top bar.
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

const myAccount: Account = {
  name: { firstName: 'Ada', lastName: 'Lovelace' },
  contactDetails: { email: 'ada@babbage.test', phoneNumber: null, language: null, links: [] },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

const json = (body: unknown) =>
  new Response(JSON.stringify(body), { status: 200, headers: { 'Content-Type': 'application/json' } })

const aDashboard = {
  organizationName: 'Babbage & Co',
  markers: [],
  stats: {
    activeOrganizations: 66,
    locatedOrganizations: 49,
    contacts: 97,
    exchanges: 34,
    exchangesThisMonth: 26,
  },
}

const nothingToProcess = {
  items: [],
  toDos: [],
  contactsToAttach: [],
  _links: { self: { href: '/api/me/exchanges-to-process', method: 'GET' } },
}

const anEmptyDay = { today: [], next: [], _links: { self: { href: '/api/me/day', method: 'GET' } } }

let answers: Record<string, unknown> = {}

const myPages: Record<string, unknown> = {
  '/api/me': myAccount,
  '/api/dashboard': aDashboard,
  '/api/me/day': anEmptyDay,
  '/api/me/exchanges-to-process': nothingToProcess,
  '/api/organizations': { items: [], _links: {} },
}

function pathOf(input: Parameters<typeof globalThis.fetch>[0]) {
  return new URL(input instanceof Request ? input.url : String(input)).pathname
}

/** The API answers my account, the dashboard and its organizations, my day and what I have to process. It serves nothing else. */
function theApiAnswersMyPages() {
  theApi.mockImplementation(async (input) => {
    const answer = answers[pathOf(input)]
    return answer === undefined ? new Response(null, { status: 404 }) : json(answer)
  })
}

/** A navigation entry, by the name it starts with: its count follows it. */
function theEntry(name: string) {
  return within(screen.getByRole('navigation', { name: 'Navigation principale' })).getByRole('link', {
    name: new RegExp(`^${name}`),
  })
}

function theSidebar() {
  return screen.getByRole('complementary')
}

describe('the shell of a signed-in member', () => {
  beforeEach(() => {
    theApi.mockReset()
    localStorage.clear()
    usePreferences.setState({ sidebarCollapsed: false })
    answers = { ...myPages }
    theApiAnswersMyPages()
  })

  it('shows the brand, then the name of my account, above the navigation', async () => {
    await renderTheSpaAt('/')

    await screen.findByRole('navigation', { name: 'Navigation principale' })
    expect(within(theSidebar()).getByRole('link', { name: 'Prospecter' })).toHaveAttribute('href', '/')
    expect(within(theSidebar()).getByText('Babbage & Co')).toBeInTheDocument()
  })

  it('counts the organizations and the contacts beside their entries, from the figures the dashboard read', async () => {
    await renderTheSpaAt('/')

    await screen.findByRole('heading', { name: 'Bonjour Ada' })
    expect(theEntry('Organisations')).toHaveTextContent(/^Organisations66/)
    expect(theEntry('Organisations')).toHaveAccessibleName(/66 organisations prospectées$/)
    expect(theEntry('Contacts')).toHaveTextContent(/^Contacts97/)
    expect(theEntry('Contacts')).toHaveAccessibleName(/97 contacts$/)
  })

  it('counts nothing where there is nothing to count', async () => {
    answers['/api/dashboard'] = {
      ...aDashboard,
      stats: { ...aDashboard.stats, activeOrganizations: 0, contacts: 0 },
    }
    await renderTheSpaAt('/')

    await screen.findByRole('heading', { name: 'Bonjour Ada' })
    expect(theEntry('Organisations')).toHaveTextContent(/^Organisations$/)
    expect(theEntry('Contacts')).toHaveTextContent(/^Contacts$/)
  })

  it('counts nothing that the app did not read', async () => {
    await renderTheSpaAt('/calendar')

    await screen.findByRole('navigation', { name: 'Navigation principale' })
    expect(theEntry('Organisations')).toHaveTextContent(/^Organisations$/)
    expect(theEntry('Contacts')).toHaveTextContent(/^Contacts$/)
    expect(theApi.mock.calls.map(([input]) => pathOf(input))).not.toContain('/api/dashboard')
  })

  it('folds the sidebar to its icons, each entry explained by a tooltip', async () => {
    await renderTheSpaAt('/')
    await screen.findByRole('heading', { name: 'Bonjour Ada' })

    await userEvent.click(screen.getByRole('button', { name: 'Replier la barre latérale' }))

    expect(within(theSidebar()).queryByText('Babbage & Co')).not.toBeInTheDocument()
    await userEvent.hover(theEntry('Contacts'))
    expect(await screen.findByRole('tooltip', { name: 'Contacts' })).toBeInTheDocument()
  })

  it('explains no entry by a tooltip while the sidebar is unfolded: their names show', async () => {
    await renderTheSpaAt('/')
    await screen.findByRole('heading', { name: 'Bonjour Ada' })

    await userEvent.hover(theEntry('Contacts'))

    await new Promise((resolve) => setTimeout(resolve, 300))
    expect(screen.queryByRole('tooltip')).not.toBeInTheDocument()
  })

  it('keeps only my avatar at the foot of the folded sidebar, my name left to its menu', async () => {
    usePreferences.setState({ sidebarCollapsed: true })
    await renderTheSpaAt('/')
    await screen.findByRole('heading', { name: 'Bonjour Ada' })

    const myAvatar = within(theSidebar()).getByRole('button', { name: 'Ada Lovelace, menu du compte' })
    expect(myAvatar).toHaveTextContent(/^AL$/)
  })

  it('remembers the sidebar folded at the next visit', async () => {
    await renderTheSpaAt('/')
    await screen.findByRole('heading', { name: 'Bonjour Ada' })
    await userEvent.click(screen.getByRole('button', { name: 'Replier la barre latérale' }))

    expect(JSON.parse(localStorage.getItem('prospecter-preferences') ?? '{}').state).toMatchObject({
      sidebarCollapsed: true,
    })
  })

  it('unfolds the sidebar it folded', async () => {
    usePreferences.setState({ sidebarCollapsed: true })
    await renderTheSpaAt('/')
    await screen.findByRole('heading', { name: 'Bonjour Ada' })

    await userEvent.click(screen.getByRole('button', { name: 'Déplier la barre latérale' }))

    expect(within(theSidebar()).getByText('Babbage & Co')).toBeInTheDocument()
  })

  it("leads from the sidebar's foot to the settings", async () => {
    await renderTheSpaAt('/')
    await screen.findByRole('heading', { name: 'Bonjour Ada' })

    expect(within(theSidebar()).getByRole('link', { name: 'Réglages' })).toHaveAttribute('href', '/settings')
  })

  it("opens my avatar's menu at the sidebar's foot: my account, the theme and the sign-out", async () => {
    await renderTheSpaAt('/')
    await screen.findByRole('heading', { name: 'Bonjour Ada' })

    const myAvatar = within(theSidebar()).getByRole('button', { name: 'Ada Lovelace, menu du compte' })
    expect(myAvatar).toHaveTextContent(/^ALAda Lovelace$/)
    await userEvent.click(myAvatar)

    expect(await screen.findByRole('menuitem', { name: 'Mon compte' })).toHaveAttribute('href', '/account')
    expect(screen.getByRole('menuitemradio', { name: 'Clair' })).toBeInTheDocument()
    expect(screen.getByRole('menuitemradio', { name: 'Sombre' })).toBeInTheDocument()
    expect(screen.getByRole('menuitemradio', { name: 'Système' })).toBeInTheDocument()
    expect(screen.getByRole('menuitem', { name: 'Se déconnecter' })).toBeInTheDocument()
    expect(screen.queryByRole('menuitem', { name: 'Configuration' })).not.toBeInTheDocument()
  })

  it("shows the page's breadcrumb in the top bar, from my account to the page shown", async () => {
    await renderTheSpaAt('/calendar')

    const breadcrumb = await screen.findByRole('navigation', { name: "Fil d'Ariane" })
    expect(breadcrumb).toHaveTextContent(/^Babbage & Co.*Calendrier$/)
    expect(within(breadcrumb).getByText('Calendrier')).toHaveAttribute('aria-current', 'page')
    expect(within(breadcrumb).queryByRole('link', { name: 'Calendrier' })).not.toBeInTheDocument()
  })

  it.each([
    ['/', 'Tableau de bord'],
    ['/settings', 'Réglages'],
    ['/account', 'Mon compte'],
  ])('names the section of %s in the breadcrumb, %s', async (path, section) => {
    await renderTheSpaAt(path)

    const breadcrumb = await screen.findByRole('navigation', { name: "Fil d'Ariane" })
    expect(breadcrumb).toHaveTextContent(new RegExp(`^Babbage & Co${section}$`))
  })

  it('leads by the breadcrumb of a page back to its section', async () => {
    await renderTheSpaAt('/organizations/new')

    const breadcrumb = await screen.findByRole('navigation', { name: "Fil d'Ariane" })
    expect(within(breadcrumb).getByRole('link', { name: 'Organisations' })).toHaveAttribute(
      'href',
      '/organizations',
    )
  })

  it('shows the day and its week number in the top bar', async () => {
    await renderTheSpaAt('/')

    const theTopBar = (await screen.findByRole('navigation', { name: "Fil d'Ariane" })).closest('header')
    expect(theTopBar).toContainElement(screen.getByText('mer. 18 nov. · S47'))
  })

  it('offers « Nouveau » in gold in the top bar', async () => {
    await renderTheSpaAt('/')

    const theTopBar = (await screen.findByRole('navigation', { name: "Fil d'Ariane" })).closest('header')
    const theNewButton = within(theTopBar as HTMLElement).getByRole('button', { name: 'Nouveau' })
    expect(theNewButton).toHaveAttribute('data-variant', 'gold')
  })
})
