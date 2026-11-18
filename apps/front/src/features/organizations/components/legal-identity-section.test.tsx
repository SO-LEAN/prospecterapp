import { QueryClient, QueryClientProvider, useSuspenseQuery } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Suspense } from 'react'
import { describe, expect, it, vi } from 'vitest'
import { TooltipProvider } from '@/shared/ui/tooltip'
import { type Organization, organizationQuery } from '../queries'
import { LegalIdentitySection } from './legal-identity-section'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

const ACME = '0190a1c0-0000-7000-8000-000000000001'
const A_SIRET = '55210055400013'

const anOrganization = (organization: Partial<Organization> = {}): Organization => ({
  id: ACME,
  name: 'ACME',
  legalIdentity: { corporateName: 'ACME', siret: null },
  contactDetails: {},
  contactIds: [],
  createdAt: '2026-10-15T12:30:00+00:00',
  modifiedAt: '2026-10-15T12:30:00+00:00',
  _links: {
    self: { href: `/api/organizations/${ACME}`, method: 'GET' },
    reviseLegalIdentity: { href: `/api/organizations/${ACME}/legal-identity`, method: 'PATCH' },
    searchEstablishments: { href: `/api/organizations/${ACME}/establishments`, method: 'GET' },
  },
  ...organization,
})

function TheIdentityOfTheOrganization() {
  const { data: organization } = useSuspenseQuery(organizationQuery(ACME))

  return <LegalIdentitySection organization={organization} />
}

function renderTheSection(organization: Organization) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY, retry: false } },
  })
  queryClient.setQueryData(organizationQuery(ACME).queryKey, organization)
  render(
    <QueryClientProvider client={queryClient}>
      <TooltipProvider>
        <Suspense>
          <TheIdentityOfTheOrganization />
        </Suspense>
      </TooltipProvider>
    </QueryClientProvider>,
  )
  return userEvent.setup()
}

const sentTo = async (path: string) => {
  const request = theApi.mock.calls.map(([sent]) => sent as Request).find((sent) => sent.url.endsWith(path))
  return request?.json()
}

async function openTheIdentity(user: ReturnType<typeof userEvent.setup>) {
  await user.click(screen.getByRole('button', { name: /^Modifier.*Identité$/ }))
}

describe('the legal identity of an organization', () => {
  it('shows its SIRET, leading to its establishment in the public directory', () => {
    renderTheSection(anOrganization({ legalIdentity: { corporateName: 'ACME', siret: A_SIRET } }))

    expect(screen.getByRole('link', { name: '552 100 554 00013' })).toHaveAttribute(
      'href',
      `https://annuaire-entreprises.data.gouv.fr/etablissement/${A_SIRET}`,
    )
    expect(theApi).not.toHaveBeenCalled()
  })

  it('notes the SIRET typed with its identity', async () => {
    const user = renderTheSection(anOrganization())
    theApi.mockResolvedValue(
      Response.json(anOrganization({ legalIdentity: { corporateName: 'ACME', siret: A_SIRET } })),
    )

    await openTheIdentity(user)
    await user.type(screen.getByLabelText(/^SIRET/), '552 100 554 00013')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await sentTo('/legal-identity')).toEqual({
      corporateName: 'ACME',
      form: null,
      type: null,
      siret: '552 100 554 00013',
    })
  })

  it('shows a SIRET another organization of the account has on its field, naming that organization', async () => {
    const user = renderTheSection(anOrganization())
    theApi.mockResolvedValue(
      Response.json(
        {
          title: 'Validation Failed',
          status: 422,
          violations: [
            {
              propertyPath: 'siret',
              title: 'SIRET already the one of ACME Lyon',
              reason: 'siretAlreadyUsed',
              context: { organization: 'ACME Lyon' },
            },
          ],
        },
        { status: 422, headers: { 'Content-Type': 'application/problem+json' } },
      ),
    )

    await openTheIdentity(user)
    await user.type(screen.getByLabelText(/^SIRET/), '55210055400013')
    await user.click(screen.getByRole('button', { name: 'Enregistrer' }))

    expect(await screen.findByText('Ce SIRET est déjà celui d’ACME Lyon.')).toBeInTheDocument()
    expect(screen.getByLabelText(/^SIRET/)).toHaveAttribute('aria-invalid', 'true')
  })
})
