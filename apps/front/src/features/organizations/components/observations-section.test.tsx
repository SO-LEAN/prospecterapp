import { QueryClient, QueryClientProvider, useSuspenseQuery } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Suspense } from 'react'
import { describe, expect, it, vi } from 'vitest'
import { type Organization, organizationQuery } from '../queries'
import { ObservationsSection } from './observations-section'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ACME = '0190a1c0-0000-7000-8000-000000000001'

const anOrganization = (organization: Partial<Organization> = {}): Organization => ({
  id: ACME,
  revision: 1,
  name: 'ACME',
  legalIdentity: { corporateName: 'ACME' },
  contactDetails: { links: [] },
  contactIds: [],
  possibleDuplicates: [],
  createdByAnAI: false,
  createdAt: '2026-10-15T12:30:00+00:00',
  modifiedAt: '2026-10-15T12:30:00+00:00',
  _links: {
    self: { href: `/api/organizations/${ACME}`, method: 'GET' },
    writeObservations: { href: `/api/organizations/${ACME}/observations`, method: 'PATCH' },
  },
  ...organization,
})

function TheObservationsOfTheOrganization() {
  const { data: organization } = useSuspenseQuery(organizationQuery(ACME))

  return <ObservationsSection organization={organization} />
}

function renderTheSection(organization: Organization) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(organizationQuery(ACME).queryKey, organization)
  render(
    <QueryClientProvider client={queryClient}>
      <Suspense>
        <TheObservationsOfTheOrganization />
      </Suspense>
    </QueryClientProvider>,
  )
  return userEvent.setup()
}

const requestTo = (path: string) =>
  theApi.mock.calls.map(([sent]) => sent as Request).find((sent) => new URL(sent.url).pathname === path)

describe('the observations on an organization', () => {
  it('are saved on the revision the organization was read at', async () => {
    const user = renderTheSection(anOrganization({ revision: 7 }))
    theApi.mockResolvedValue(Response.json(anOrganization({ revision: 8, observations: 'Rappeler lundi' })))

    await user.click(screen.getByRole('button', { name: /^Modifier.*Observations$/ }))
    await user.type(screen.getByLabelText('Observations'), 'Rappeler lundi')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByText('Rappeler lundi')).toBeInTheDocument()
    const sent = requestTo(`/api/organizations/${ACME}/observations`)
    expect(new URL(sent?.url ?? 'http://none').searchParams.get('revision')).toBe('7')
    expect(await sent?.json()).toEqual({ observations: 'Rappeler lundi' })
  })
})
