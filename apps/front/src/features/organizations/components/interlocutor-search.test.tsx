import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { InterlocutorSearch } from './interlocutor-search'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ADA = '0190a1c0-0000-7000-8000-00000000a000'
const ADAM = '0190a1c0-0000-7000-8000-00000000b000'

/** The contacts and the organizations whose name starts with the text searched. */
function theApiFinds(contacts: unknown[], organizations: unknown[]) {
  theApi.mockImplementation(async (input) => {
    const { pathname } = new URL((input as Request).url)
    const items = pathname === '/api/contacts' ? contacts : organizations
    return Response.json({ items, _links: { self: { href: pathname, method: 'GET' } } })
  })
}

const searched = () => theApi.mock.calls.map(([request]) => new URL((request as Request).url).search)

function renderTheSearch(onChoose = vi.fn()) {
  render(
    <QueryClientProvider client={new QueryClient()}>
      <InterlocutorSearch onChoose={onChoose} />
    </QueryClientProvider>,
  )
  return onChoose
}

afterEach(() => {
  theApi.mockReset()
})

describe('InterlocutorSearch', () => {
  it('finds the contacts, then the organizations, by the start of what is typed; one is chosen in a click', async () => {
    theApiFinds(
      [{ id: ADA, name: { firstName: 'Ada', lastName: 'Lovelace' } }],
      [{ id: ADAM, name: 'Adam Industries' }],
    )
    const onChoose = renderTheSearch()

    await userEvent.type(screen.getByLabelText('Chercher un contact ou une organisation'), 'Ada')

    const found = await screen.findAllByRole('button')
    expect(found.map((button) => button.textContent?.trim())).toEqual(['Ada Lovelace', 'Adam Industries'])
    expect(searched()).toEqual(['?q=Ada', '?q=Ada'])
    await userEvent.click(screen.getByRole('button', { name: 'Adam Industries' }))
    expect(onChoose).toHaveBeenCalledWith({ kind: 'organization', id: ADAM, name: 'Adam Industries' })
  })

  it('names a contact without a name, and says when nobody starts so', async () => {
    theApiFinds([{ id: ADA, name: {} }], [])
    renderTheSearch()

    await userEvent.type(screen.getByLabelText('Chercher un contact ou une organisation'), 'x')
    expect(await screen.findByRole('button', { name: 'Contact sans nom' })).toBeInTheDocument()

    theApiFinds([], [])
    await userEvent.type(screen.getByLabelText('Chercher un contact ou une organisation'), 'yz')
    expect(await screen.findByText('Aucun contact ni organisation ne commence ainsi.')).toBeInTheDocument()
  })

  it('asks nothing before a text is typed', () => {
    renderTheSearch()

    expect(theApi).not.toHaveBeenCalled()
  })
})
