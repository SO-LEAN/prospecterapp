import { screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Account } from '@/features/account/mutations'
import type { Contact, ContactInList } from '@/features/contacts/queries'
import type { ContactTags, SuggestedTags } from '@/features/tags/queries'
import { renderTheSpaAt } from '@/test/render-the-spa'

/** The contacts carrying every tag chosen, on the routes of the SPA: the filter kept in the url, the count. */

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

const ADA = '0190a1c0-0000-7000-8000-000000000001'
const GRACE = '0190a1c0-0000-7000-8000-000000000002'
const LINUS = '0190a1c0-0000-7000-8000-000000000003'

const me: Account = {
  name: { firstName: 'Alan', lastName: 'Turing' },
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  organization: { legalIdentity: { corporateName: 'Babbage & Co' } },
  _links: { self: { href: '/api/me', method: 'GET' } },
}

const inList = (id: string, firstName: string, lastName: string): ContactInList => ({
  id,
  name: { firstName, lastName },
  civility: 'x',
  jobTitle: null,
  contactDetails: { links: [] },
  masked: false,
  organizations: [],
  tags: ['#ESN'],
  contact: { href: `/api/contacts/${id}`, method: 'GET' },
})

const ada = inList(ADA, 'Ada', 'Lovelace')
const grace = inList(GRACE, 'Grace', 'Hopper')
const linus = inList(LINUS, 'Linus', 'Torvalds')

const suggested: SuggestedTags = {
  families: [{ family: 'organizationType', tags: ['#ESN'] }],
  own: [],
  automatic: ['#EMAIL', '#PHONE', '#SMS', '#LINKEDIN'],
}

/** Who carries which tags: the api answers those carrying every tag asked, newest first, counted. */
const TAGGED: Record<string, ContactInList[]> = { ESN: [grace, ada], 'ESN,EMAIL': [ada], EMAIL: [ada] }

const theCard: Contact = {
  id: ADA,
  name: { firstName: 'Ada', lastName: 'Lovelace' },
  possibleDuplicates: [],
  civility: 'x',
  contactDetails: { email: 'ada@acme.test', phoneNumber: null, language: null, links: [] },
  masked: false,
  _links: {
    self: { href: `/api/contacts/${ADA}`, method: 'GET' },
    tags: { href: `/api/contacts/${ADA}/tags`, method: 'GET' },
  },
}

const itsTags: ContactTags = {
  tags: [],
  inherited: [{ name: '#ESN', from: [] }],
  automatic: ['#EMAIL'],
  _links: {
    self: { href: `/api/contacts/${ADA}/tags`, method: 'GET' },
    tag: { href: `/api/contacts/${ADA}/tags`, method: 'POST' },
  },
}

/** The api: my account, the whole list of the contacts, those tagged, the tags proposed, the card of Ada. */
function theApiAnswers() {
  const tagsAsked: string[] = []
  theApi.mockImplementation(async (input) => {
    const url = new URL(input instanceof Request ? input.url : String(input))
    if (url.pathname === '/api/tagged-contacts') {
      const tags = url.searchParams.get('tags') ?? ''
      tagsAsked.push(tags)
      const found = TAGGED[tags] ?? []
      return Response.json({
        items: found,
        found: found.length,
        _links: { self: { href: `/api/tagged-contacts?tags=${tags}`, method: 'GET' } },
      })
    }
    const answers: Record<string, unknown> = {
      '/api/me': me,
      '/api/me/connections': {
        connections: [],
        _links: { self: { href: '/api/me/connections', method: 'GET' } },
      },
      '/api/me/reminders': {
        enabled: false,
        minutesBefore: 10,
        _links: { self: { href: '/api/me/reminders', method: 'GET' } },
      },
      '/api/me/day': { today: [], next: [], _links: { self: { href: '/api/me/day', method: 'GET' } } },
      '/api/me/exchanges-to-process': { items: [], toDos: [], contactsToAttach: [], _links: {} },
      '/api/contacts': {
        items: [linus, grace, ada],
        _links: {
          self: { href: '/api/contacts', method: 'GET' },
          search: { href: '/api/contacts', method: 'GET' },
          searchByTags: { href: '/api/tagged-contacts', method: 'GET' },
        },
      },
      '/api/tags': suggested,
      [`/api/contacts/${ADA}`]: theCard,
      [`/api/contacts/${ADA}/tags`]: itsTags,
    }
    return Response.json(answers[url.pathname] ?? { items: [], _links: {} })
  })
  return tagsAsked
}

const namesListed = () =>
  screen.getAllByRole('link', { name: /Lovelace|Hopper|Torvalds/ }).map((link) => link.textContent)

describe('my contacts by their tags', () => {
  afterEach(() => theApi.mockReset())

  it('lists the contacts carrying the tags of the url, and says how many there are', async () => {
    const tagsAsked = theApiAnswers()

    await renderTheSpaAt('/contacts?tags=ESN')

    expect(await screen.findByText('2 contacts trouvés')).toBeInTheDocument()
    expect(namesListed()).toEqual(['Grace Hopper', 'Ada Lovelace'])
    expect(within(screen.getByRole('list', { name: 'Tags choisis' })).getByText('#ESN')).toBeInTheDocument()
    expect(tagsAsked).toContain('ESN')
  })

  it('narrows the list to the contacts carrying every tag added, the automatic ones proposed too', async () => {
    const tagsAsked = theApiAnswers()
    const router = await renderTheSpaAt('/contacts?tags=ESN')
    await screen.findByText('2 contacts trouvés')

    await userEvent.click(screen.getByRole('button', { name: 'Filtrer par tag' }))
    await userEvent.type(await screen.findByRole('combobox', { name: 'Tag' }), 'mail')
    await userEvent.click(await screen.findByRole('option', { name: '#EMAIL' }))

    expect(await screen.findByText('1 contact trouvé')).toBeInTheDocument()
    expect(namesListed()).toEqual(['Ada Lovelace'])
    expect(router.state.location.search.tags).toBe('ESN,EMAIL')
    expect(tagsAsked).toContain('ESN,EMAIL')
  })

  it('widens the list as a tag is taken off, to the whole list once none is left', async () => {
    theApiAnswers()
    const router = await renderTheSpaAt('/contacts?tags=ESN')
    await screen.findByText('2 contacts trouvés')

    await userEvent.click(screen.getByRole('button', { name: 'Retirer le filtre #ESN' }))

    await waitFor(() => expect(namesListed()).toEqual(['Linus Torvalds', 'Grace Hopper', 'Ada Lovelace']))
    expect(screen.queryByText(/trouvés?$/)).not.toBeInTheDocument()
    expect(router.state.location.search.tags ?? '').toBe('')
  })

  it('leaves the search by text aside while tags are chosen', async () => {
    theApiAnswers()
    const router = await renderTheSpaAt('/contacts?q=hop')
    await screen.findByRole('searchbox')

    await userEvent.click(screen.getByRole('button', { name: 'Filtrer par tag' }))
    await userEvent.click(await screen.findByRole('option', { name: '#ESN' }))

    await screen.findByText('2 contacts trouvés')
    expect(router.state.location.search).toMatchObject({ tags: 'ESN', q: '' })
  })

  it('opens the list of the contacts carrying a tag pressed on the card of a contact', async () => {
    theApiAnswers()
    const router = await renderTheSpaAt(`/contacts/${ADA}`)

    await userEvent.click(await screen.findByRole('link', { name: '#EMAIL' }))

    expect(await screen.findByText('1 contact trouvé')).toBeInTheDocument()
    expect(router.state.location.pathname).toBe('/contacts')
    expect(router.state.location.search.tags).toBe('EMAIL')
  })
})
