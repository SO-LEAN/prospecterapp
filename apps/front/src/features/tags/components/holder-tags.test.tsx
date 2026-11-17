import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { SuggestedTags, Tags } from '../queries'
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
}

const tagsOf = (base: string, names: string[]): Tags => ({
  tags: names.map((name) => ({
    name,
    _links: { takeOff: { href: `${base}/tags/${name.slice(1)}`, method: 'DELETE' } },
  })),
  _links: { self: { href: `${base}/tags`, method: 'GET' }, tag: { href: `${base}/tags`, method: 'POST' } },
})

type Call = { method: string; pathname: string; body: unknown }

/** The api: the tags proposed, and those of what is tagged, as each gesture leaves them. */
function theApiAnswers(base: string, carried: string[], afterTheGesture: string[]) {
  const calls: Call[] = []
  theApi.mockImplementation(async (input) => {
    const request = input as Request
    const { pathname } = new URL(request.url)
    const body = request.method === 'GET' ? null : await request.text()
    calls.push({ method: request.method, pathname, body: body ? JSON.parse(body) : null })
    if (pathname === '/api/tags') return Response.json(suggested)
    return Response.json(tagsOf(base, request.method === 'GET' ? carried : afterTheGesture))
  })
  return calls
}

const renderWithACache = (node: React.ReactNode) =>
  render(<QueryClientProvider client={new QueryClient()}>{node}</QueryClientProvider>)

describe('the tags of an organization or of a contact', () => {
  afterEach(() => theApi.mockReset())

  it('shows the tags of the organization', async () => {
    theApiAnswers(`/api/organizations/${ACME}`, ['#APPORTEUR', '#ESN'], [])

    renderWithACache(<OrganizationTags organizationId={ACME} />)

    expect(await screen.findByText('#APPORTEUR')).toBeInTheDocument()
    expect(screen.getByText('#ESN')).toBeInTheDocument()
  })

  it('puts a tag picked on the organization, and shows the tags the api answers', async () => {
    const calls = theApiAnswers(`/api/organizations/${ACME}`, [], ['#ESN'])
    renderWithACache(<OrganizationTags organizationId={ACME} />)

    await userEvent.click(await screen.findByRole('button', { name: 'Ajouter un tag' }))
    await userEvent.click(await screen.findByRole('option', { name: '#ESN' }))

    expect(await screen.findByText('#ESN')).toBeInTheDocument()
    expect(calls).toContainEqual({ method: 'POST', pathname: `/api/organizations/${ACME}/tags`, body: { tags: ['#ESN'] } })
  })

  it('takes a tag off the contact by its cross', async () => {
    const calls = theApiAnswers(`/api/contacts/${ADA}`, ['#CLIENT'], [])
    renderWithACache(<ContactTags contactId={ADA} />)

    await userEvent.click(await screen.findByRole('button', { name: 'Retirer le tag #CLIENT' }))

    await waitFor(() => expect(screen.queryByText('#CLIENT')).not.toBeInTheDocument())
    expect(calls).toContainEqual({ method: 'DELETE', pathname: `/api/contacts/${ADA}/tags/CLIENT`, body: null })
  })
})
