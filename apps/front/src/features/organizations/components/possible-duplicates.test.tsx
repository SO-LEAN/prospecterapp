import { QueryClient, QueryClientProvider, useSuspenseQuery } from '@tanstack/react-query'
import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import { type Organization, organizationQuery } from '../queries'
import { PossibleDuplicates } from './possible-duplicates'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const DUPLICATE = '0190a1c0-0000-7000-8000-000000000001'
const ACME = '0190a1c0-0000-7000-8000-000000000002'
const DISMISS = `/api/organizations/${DUPLICATE}/possible-duplicates/${ACME}`

const aDuplicate = (possibleDuplicates: Organization['possibleDuplicates']): Organization => ({
  id: DUPLICATE,
  name: 'ACME s.a.s',
  legalIdentity: { corporateName: 'ACME s.a.s' },
  contactDetails: {},
  contactIds: [],
  possibleDuplicates,
  createdAt: '2026-11-20T09:00:00+00:00',
  modifiedAt: '2026-11-20T09:00:00+00:00',
  _links: { self: { href: `/api/organizations/${DUPLICATE}`, method: 'GET' } },
})

const acme = (links: { dismiss?: { href: string; method: string } }) => ({
  id: ACME,
  name: 'Acme SAS',
  _links: { self: { href: `/api/organizations/${ACME}`, method: 'GET' }, ...links },
})

/** As the sheet reads it: from the cache the gestures update. */
function TheSheet({ id }: { id: string }) {
  const { data: organization } = useSuspenseQuery(organizationQuery(id))

  return <PossibleDuplicates organization={organization} />
}

async function renderThePossibleDuplicatesOf(organization: Organization) {
  const queryClient = new QueryClient()
  queryClient.setQueryData(organizationQuery(organization.id).queryKey, organization)
  await renderInARouter(
    <QueryClientProvider client={queryClient}>
      <TheSheet id={organization.id} />
    </QueryClientProvider>,
  )
  return userEvent.setup()
}

afterEach(() => {
  theApi.mockReset()
})

describe('PossibleDuplicates', () => {
  it('flags the organization as a possible duplicate, with a link to the one it resembles', async () => {
    await renderThePossibleDuplicatesOf(aDuplicate([acme({})]))

    expect(screen.getByText('Doublon possible')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Acme SAS' })).toHaveAttribute('href', `/organizations/${ACME}`)
  })

  it('does not offer to say it is no duplicate when the organization does not carry its link', async () => {
    await renderThePossibleDuplicatesOf(aDuplicate([acme({})]))

    expect(screen.queryByRole('button', { name: /Ce n’est pas un doublon/ })).not.toBeInTheDocument()
  })

  it('says, once checked, that it is no duplicate of the organization it resembles', async () => {
    theApi.mockImplementation(async () => Response.json(aDuplicate([])))
    const user = await renderThePossibleDuplicatesOf(
      aDuplicate([acme({ dismiss: { href: DISMISS, method: 'DELETE' } })]),
    )

    await user.click(screen.getByRole('button', { name: 'Ce n’est pas un doublon d’Acme SAS' }))

    const request = theApi.mock.calls[0]?.[0] as Request
    expect([request.method, new URL(request.url).pathname]).toEqual(['DELETE', DISMISS])
    await waitFor(() => expect(screen.queryByText('Doublon possible')).not.toBeInTheDocument())
  })
})
