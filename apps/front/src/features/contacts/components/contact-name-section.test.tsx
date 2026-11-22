import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import type { Contact } from '../queries'
import { NameSection } from './contact-sections'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const CAMILLE = '0190a1c0-0000-7000-8000-000000000001'

const camille = (links: Partial<Contact['_links']>, civility: Contact['title'] = 'x'): Contact => ({
  id: CAMILLE,
  revision: 1,
  name: { firstName: 'Camille', lastName: 'Martin' },
  possibleDuplicates: [],
  createdByAnAI: false,
  title: civility,
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  masked: false,
  _links: { self: { href: `/api/contacts/${CAMILLE}`, method: 'GET' }, ...links },
})

const theRightToChangeIt = {
  rename: { href: `/api/contacts/${CAMILLE}/name`, method: 'PATCH' },
  changeTitle: { href: `/api/contacts/${CAMILLE}/title`, method: 'PATCH' },
}

function renderTheSection(contact: Contact) {
  render(
    <QueryClientProvider client={new QueryClient()}>
      <NameSection contact={contact} />
    </QueryClientProvider>,
  )
  return userEvent.setup()
}

afterEach(() => theApi.mockReset())

describe('the name of a contact', () => {
  it('shows the civility noted beside the name', () => {
    renderTheSection(camille({}, 'mr'))

    expect(screen.getByText('Civilité').nextSibling).toHaveTextContent('Monsieur')
  })

  it('notes the civility with the name, never required', async () => {
    const sent: Array<{ url: string; body: unknown }> = []
    theApi.mockImplementation(async (input) => {
      const request = input as Request
      sent.push({ url: new URL(request.url).pathname, body: await request.clone().json() })
      return Response.json(camille(theRightToChangeIt, 'ms'))
    })
    const user = renderTheSection(camille(theRightToChangeIt))

    await user.click(screen.getByRole('button', { name: /^Modifier\s:\sNom$/ }))
    await user.click(screen.getByRole('combobox', { name: /^Civilité/ }))
    await user.click(screen.getByRole('option', { name: 'Madame' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await vi.waitFor(() =>
      expect(sent).toContainEqual({ url: `/api/contacts/${CAMILLE}/title`, body: { title: 'ms' } }),
    )
  })

  it('renames on the revision the contact was read at, then notes the title on the one the rename answered', async () => {
    const revisions: Array<{ url: string; revision: string | null }> = []
    theApi.mockImplementation(async (input) => {
      const url = new URL((input as Request).url)
      revisions.push({ url: url.pathname, revision: url.searchParams.get('revision') })
      return Response.json({ ...camille(theRightToChangeIt, 'ms'), revision: 8 })
    })
    const user = renderTheSection({ ...camille(theRightToChangeIt), revision: 7 })

    await user.click(screen.getByRole('button', { name: /^Modifier\s:\sNom$/ }))
    await user.click(screen.getByRole('combobox', { name: /^Civilité/ }))
    await user.click(screen.getByRole('option', { name: 'Madame' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await vi.waitFor(() =>
      expect(revisions).toEqual([
        { url: `/api/contacts/${CAMILLE}/name`, revision: '7' },
        { url: `/api/contacts/${CAMILLE}/title`, revision: '8' },
      ]),
    )
  })

  it('removes the civility when none is chosen again', async () => {
    const sent: Array<{ url: string; body: unknown }> = []
    theApi.mockImplementation(async (input) => {
      const request = input as Request
      sent.push({ url: new URL(request.url).pathname, body: await request.clone().json() })
      return Response.json(camille(theRightToChangeIt))
    })
    const user = renderTheSection(camille(theRightToChangeIt, 'mr'))

    await user.click(screen.getByRole('button', { name: /^Modifier\s:\sNom$/ }))
    await user.click(screen.getByRole('combobox', { name: /^Civilité/ }))
    await user.click(screen.getByRole('option', { name: 'Non renseignée' }))
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await vi.waitFor(() =>
      expect(sent).toContainEqual({ url: `/api/contacts/${CAMILLE}/title`, body: { title: 'x' } }),
    )
  })

  it('does not send the civility when it did not change', async () => {
    const sent: string[] = []
    theApi.mockImplementation(async (input) => {
      sent.push(new URL((input as Request).url).pathname)
      return Response.json(camille(theRightToChangeIt, 'ms'))
    })
    const user = renderTheSection(camille(theRightToChangeIt, 'ms'))

    await user.click(screen.getByRole('button', { name: /^Modifier\s:\sNom$/ }))
    await user.type(screen.getByLabelText('Prénom'), 's')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    await vi.waitFor(() => expect(sent).toEqual([`/api/contacts/${CAMILLE}/name`]))
  })
})
