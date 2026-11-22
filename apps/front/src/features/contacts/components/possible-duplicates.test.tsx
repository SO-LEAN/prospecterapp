import { QueryClient, QueryClientProvider, useSuspenseQuery } from '@tanstack/react-query'
import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import { type Contact, contactQuery } from '../queries'
import { PossibleDuplicatesOfContact } from './possible-duplicates'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const DUPLICATE = '0190a1c0-0000-7000-8000-000000000001'
const ADA = '0190a1c0-0000-7000-8000-000000000002'
const DISMISS = `/api/contacts/${DUPLICATE}/possible-duplicates/${ADA}`

const aDuplicate = (possibleDuplicates: Contact['possibleDuplicates']): Contact => ({
  id: DUPLICATE,
  revision: 1,
  name: { firstName: 'ada', lastName: 'LOVELACE' },
  possibleDuplicates,
  createdByAnAI: false,
  title: 'x',
  contactDetails: { email: null, phoneNumber: null, language: null, links: [] },
  masked: false,
  _links: { self: { href: `/api/contacts/${DUPLICATE}`, method: 'GET' } },
})

const ada = (links: { dismiss?: { href: string; method: string } }) => ({
  id: ADA,
  name: { firstName: 'Ada', lastName: 'Lovelace' },
  _links: { self: { href: `/api/contacts/${ADA}`, method: 'GET' }, ...links },
})

/** As the card reads it: from the cache the gestures update. */
function TheCard({ id }: { id: string }) {
  const { data: contact } = useSuspenseQuery(contactQuery(id))

  return <PossibleDuplicatesOfContact contact={contact} />
}

async function renderThePossibleDuplicatesOf(contact: Contact) {
  const queryClient = new QueryClient()
  queryClient.setQueryData(contactQuery(contact.id).queryKey, contact)
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <TheCard id={contact.id} />
    </QueryClientProvider>,
  )
  return userEvent.setup()
}

afterEach(() => {
  theApi.mockReset()
})

describe('PossibleDuplicatesOfContact', () => {
  it('flags the contact as a possible duplicate, with a link to the one they resemble', async () => {
    await renderThePossibleDuplicatesOf(aDuplicate([ada({})]))

    expect(screen.getByText('Doublon possible')).toBeInTheDocument()
    expect(
      screen.getByText('Ce contact ressemble, par son nom, à un autre de son organisation :'),
    ).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Ada Lovelace' })).toHaveAttribute('href', `/contacts/${ADA}`)
  })

  it('does not offer to say they are no duplicate when the contact does not carry its link', async () => {
    await renderThePossibleDuplicatesOf(aDuplicate([ada({})]))

    expect(screen.queryByRole('button', { name: /Ce n’est pas un doublon/ })).not.toBeInTheDocument()
  })

  it('says, once checked, that they are no duplicate of the contact they resemble', async () => {
    theApi.mockImplementation(async () => Response.json(aDuplicate([])))
    const user = await renderThePossibleDuplicatesOf(
      aDuplicate([ada({ dismiss: { href: DISMISS, method: 'DELETE' } })]),
    )

    const dismiss = screen.getByRole('button', { name: 'Ce n’est pas un doublon d’Ada Lovelace' })
    expect(dismiss).toHaveTextContent('Ce n’est pas un doublon')
    await user.click(dismiss)

    const request = theApi.mock.calls[0]?.[0] as Request
    expect([request.method, new URL(request.url).pathname]).toEqual(['DELETE', DISMISS])
    await waitFor(() => expect(screen.queryByText('Doublon possible')).not.toBeInTheDocument())
  })
})
