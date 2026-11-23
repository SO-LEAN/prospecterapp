import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { screen, waitFor, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { TooltipProvider } from '@/shared/ui/tooltip'
import { renderInARouter } from '@/test/render-in-a-router'
import type { ContactTags as ContactTagsResource, SuggestedTags, Tags } from '../queries'
import { ContactTags, OrganizationTags } from './holder-tags'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ACME = '0190a1c0-0000-7000-8000-000000000001'
const ADA = '0190a1c0-0000-7000-8000-000000000002'

const suggested: SuggestedTags = {
  families: [
    { family: 'organizationType', tags: ['#APPORTEUR', '#ESN'] },
    { family: 'relationship', tags: ['#CLIENT'] },
    { family: 'technology', tags: ['#PHP'] },
  ],
  own: [],
  automatic: ['#EMAIL', '#PHONE', '#SMS', '#LINKEDIN'],
}

const tagsOf = (base: string, names: string[], automatic: string[] = []): Tags => ({
  tags: names.map((name) => ({
    name,
    _links: { takeOff: { href: `${base}/tags/${name.slice(1)}`, method: 'DELETE' } },
  })),
  automatic,
  _links: { self: { href: `${base}/tags`, method: 'GET' }, tag: { href: `${base}/tags`, method: 'POST' } },
})

const contactTagsOf = (
  own: string[],
  inherited: ContactTagsResource['inherited'] = [],
  automatic: string[] = [],
): ContactTagsResource => ({
  ...tagsOf(`/api/contacts/${ADA}`, own),
  inherited,
  automatic,
})

type Call = { method: string; pathname: string; body: unknown }

/** The API double: the proposed tags, and the holder's tags as each gesture leaves them. */
function theApiAnswers(base: string, carried: string[], afterTheGesture: string[]) {
  const calls: Call[] = []
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const { pathname } = new URL(request.url)
    const body = request.method === 'GET' ? null : await request.text()
    calls.push({ method: request.method, pathname, body: body ? JSON.parse(body) : null })
    if (pathname === '/api/tags') return Response.json(suggested)
    const names = request.method === 'GET' ? carried : afterTheGesture
    return Response.json(base.startsWith('/api/contacts') ? contactTagsOf(names) : tagsOf(base, names))
  })
  return calls
}

/** The API double: Ada's tags. */
function adaCarries(tags: ContactTagsResource) {
  theApi.mockImplementation(async () => Response.json(tags))
}

/** The organizations that Ada works for, as the route gives them. */
const ADA_WORKS_FOR = [{ id: ACME, name: 'ACME' }]

const renderWithACache = (node: React.ReactNode) =>
  renderInARouter(
    <QueryClientProvider client={new QueryClient()}>
      <TooltipProvider>{node}</TooltipProvider>
    </QueryClientProvider>,
  )

describe('the tags of an organization or of a contact', () => {
  afterEach(() => theApi.mockReset())

  it('shows the tags of the organization', async () => {
    theApiAnswers(`/api/organizations/${ACME}`, ['#APPORTEUR', '#ESN'], [])

    await renderWithACache(<OrganizationTags organizationId={ACME} />)

    expect(await screen.findByText('#APPORTEUR')).toBeInTheDocument()
    expect(screen.getByText('#ESN')).toBeInTheDocument()
  })

  it('shows the tags of where the organization is after its own, deduced from its address, none taken off', async () => {
    theApi.mockImplementation(async () =>
      Response.json(tagsOf(`/api/organizations/${ACME}`, ['#ESN'], ['#STRASBOURG', '#67', '#GRAND_EST'])),
    )
    await renderWithACache(<OrganizationTags organizationId={ACME} />)

    const tags = await screen.findByRole('list', { name: 'Tags' })
    await waitFor(() =>
      expect(
        within(tags)
          .getAllByRole('listitem')
          .map((tag) => tag.textContent),
      ).toEqual(['#ESN', '#STRASBOURG', '#67', '#GRAND_EST', 'Ajouter un tag']),
    )
    expect(screen.queryByRole('button', { name: 'Retirer le tag #STRASBOURG' })).not.toBeInTheDocument()

    await userEvent.hover(await screen.findByRole('link', { name: '#STRASBOURG' }))

    expect(await screen.findByRole('tooltip')).toHaveTextContent('Déduit de l’adresse')
  })

  it('puts a tag picked on the organization, and shows the tags the api answers', async () => {
    const calls = theApiAnswers(`/api/organizations/${ACME}`, [], ['#ESN'])
    await renderWithACache(<OrganizationTags organizationId={ACME} />)

    await userEvent.click(await screen.findByRole('button', { name: 'Ajouter un tag' }))
    await userEvent.click(await screen.findByRole('option', { name: '#ESN' }))

    expect(await screen.findByText('#ESN')).toBeInTheDocument()
    expect(calls).toContainEqual({
      method: 'POST',
      pathname: `/api/organizations/${ACME}/tags`,
      body: { tags: ['#ESN'] },
    })
  })

  it('lets the member type at once in the picker, the tags proposed read once it opens', async () => {
    theApiAnswers(`/api/organizations/${ACME}`, [], [])
    await renderWithACache(<OrganizationTags organizationId={ACME} />)

    await userEvent.click(await screen.findByRole('button', { name: 'Ajouter un tag' }))

    expect(await screen.findByRole('combobox', { name: 'Tag' })).toHaveFocus()
  })

  it('takes a tag off the contact by its cross', async () => {
    const calls = theApiAnswers(`/api/contacts/${ADA}`, ['#CLIENT'], [])
    await renderWithACache(<ContactTags contactId={ADA} organizations={ADA_WORKS_FOR} />)

    await userEvent.click(await screen.findByRole('button', { name: 'Retirer le tag #CLIENT' }))

    await waitFor(() => expect(screen.queryByText('#CLIENT')).not.toBeInTheDocument())
    expect(calls).toContainEqual({
      method: 'DELETE',
      pathname: `/api/contacts/${ADA}/tags/CLIENT`,
      body: null,
    })
  })

  it('shows the tags the contact inherits and the automatic ones after its own, none taken off', async () => {
    adaCarries(contactTagsOf(['#CLIENT'], [{ name: '#ESN', from: [ACME] }], ['#EMAIL', '#SMS']))

    await renderWithACache(<ContactTags contactId={ADA} organizations={ADA_WORKS_FOR} />)

    const tags = await screen.findByRole('list', { name: 'Tags' })
    await waitFor(() =>
      expect(
        within(tags)
          .getAllByRole('listitem')
          .map((tag) => tag.textContent),
      ).toEqual(['#CLIENT', '#ESN', '#EMAIL', '#SMS', 'Ajouter un tag']),
    )
    expect(screen.getByRole('button', { name: 'Retirer le tag #CLIENT' })).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Retirer le tag #ESN' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Retirer le tag #EMAIL' })).not.toBeInTheDocument()
  })

  it('says which organization an inherited tag comes from', async () => {
    adaCarries(contactTagsOf([], [{ name: '#ESN', from: [ACME] }]))
    await renderWithACache(<ContactTags contactId={ADA} organizations={ADA_WORKS_FOR} />)

    await userEvent.hover(await screen.findByRole('link', { name: '#ESN' }))

    expect(await screen.findByRole('tooltip')).toHaveTextContent('Hérité de ACME')
  })

  it('says an inherited tag comes from an organization while their names are not read yet', async () => {
    adaCarries(contactTagsOf([], [{ name: '#ESN', from: [ACME] }]))
    await renderWithACache(<ContactTags contactId={ADA} organizations={[]} />)

    await userEvent.hover(await screen.findByRole('link', { name: '#ESN' }))

    expect(await screen.findByRole('tooltip')).toHaveTextContent('Hérité d’une organisation')
  })

  it('says an automatic tag is set automatically', async () => {
    adaCarries(contactTagsOf([], [], ['#EMAIL']))
    await renderWithACache(<ContactTags contactId={ADA} organizations={ADA_WORKS_FOR} />)

    await userEvent.hover(await screen.findByRole('link', { name: '#EMAIL' }))

    expect(await screen.findByRole('tooltip')).toHaveTextContent('Automatique')
  })

  it('leads from each tag, its own, inherited or automatic, to the contacts carrying it', async () => {
    adaCarries(contactTagsOf(['#CLIENT'], [{ name: '#ESN', from: [ACME] }], ['#EMAIL']))
    await renderWithACache(<ContactTags contactId={ADA} organizations={ADA_WORKS_FOR} />)

    expect(await screen.findByRole('link', { name: '#CLIENT' })).toHaveAttribute(
      'href',
      '/contacts?tags=CLIENT',
    )
    expect(screen.getByRole('link', { name: '#ESN' })).toHaveAttribute('href', '/contacts?tags=ESN')
    expect(screen.getByRole('link', { name: '#EMAIL' })).toHaveAttribute('href', '/contacts?tags=EMAIL')
  })
})
