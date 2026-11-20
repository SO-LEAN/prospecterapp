import { QueryClient, QueryClientProvider, useSuspenseQuery } from '@tanstack/react-query'
import { render, screen, within } from '@testing-library/react'
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
const ACME_IN_LYON = {
  siret: '55210055400021',
  name: 'ACME',
  address: '17 RUE DOCTEUR BOUCHUT 69003 LYON',
  activity: '62.02A',
  closed: true,
}
const ACME_IN_PARIS = {
  siret: A_SIRET,
  name: 'ACME',
  address: '1 RUE DE LA PAIX 75002 PARIS',
  activity: '62.02A',
  closed: false,
}

const anOrganization = (organization: Partial<Organization> = {}): Organization => ({
  id: ACME,
  name: 'ACME',
  legalIdentity: { corporateName: 'ACME', siret: null },
  contactDetails: {},
  contactIds: [],
  possibleDuplicates: [],
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
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
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

    expect(screen.getByText('SIRET')).toBeInTheDocument()
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

    expect(await sentTo(`/api/organizations/${ACME}/legal-identity`)).toEqual({
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

  it('proposes the establishments of the directory, the one chosen filling the SIRET', async () => {
    const user = renderTheSection(anOrganization())
    theApi.mockResolvedValue(Response.json({ establishments: [ACME_IN_LYON, ACME_IN_PARIS] }))

    await openTheIdentity(user)
    await user.click(screen.getByRole('button', { name: 'Rechercher dans l’annuaire' }))
    const proposals = await screen.findByRole('list', { name: 'Établissements de l’annuaire' })
    expect(screen.getByRole('dialog', { name: 'Établissements de l’annuaire' })).toHaveAccessibleDescription(
      'Les établissements de l’annuaire des entreprises dont le nom ressemble, ceux de sa ville d’abord.',
    )
    expect(theApi.mock.calls.map(([sent]) => new URL((sent as Request).url).pathname)).toEqual([
      `/api/organizations/${ACME}/establishments`,
    ])
    expect(
      within(proposals)
        .getAllByRole('listitem')
        .map((proposal) => proposal.textContent),
    ).toEqual([
      'ACME17 RUE DOCTEUR BOUCHUT 69003 LYON552 100 554 00021 · 62.02AFermé',
      'ACME1 RUE DE LA PAIX 75002 PARIS552 100 554 00013 · 62.02A',
    ])
    await user.click(within(proposals).getByRole('button', { name: /55210055400021|552 100 554 00021/ }))

    expect(screen.getByLabelText(/^SIRET/)).toHaveValue('55210055400021')
    expect(screen.queryByRole('list', { name: 'Établissements de l’annuaire' })).not.toBeInTheDocument()
  })

  it('tells the directory out of reach to try again later, the SIRET still typed by hand', async () => {
    const user = renderTheSection(anOrganization())
    theApi.mockResolvedValue(
      Response.json(
        {
          title: 'Service Unavailable',
          status: 503,
          detail: 'The directory does not answer',
          reason: 'directoryUnreachable',
        },
        { status: 503, headers: { 'Content-Type': 'application/problem+json' } },
      ),
    )

    await openTheIdentity(user)
    await user.click(screen.getByRole('button', { name: 'Rechercher dans l’annuaire' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(
      /^L’annuaire des entreprises ne répond pas pour le moment\s?: réessayez plus tard, ou tapez le SIRET\.$/,
    )
    expect(theApi).toHaveBeenCalledTimes(1)
  })

  it('says when the directory knows no establishment of its name', async () => {
    const user = renderTheSection(anOrganization())
    theApi.mockResolvedValue(Response.json({ establishments: [] }))

    await openTheIdentity(user)
    await user.click(screen.getByRole('button', { name: 'Rechercher dans l’annuaire' }))

    expect(
      await screen.findByText('L’annuaire ne connaît aucun établissement de ce nom.'),
    ).toBeInTheDocument()
  })

  it('offers no search where the organization does not lead to its establishments', async () => {
    const { searchEstablishments: _, ...links } = anOrganization()._links
    const user = renderTheSection(anOrganization({ _links: links }))

    await openTheIdentity(user)

    expect(screen.getByLabelText(/^SIRET/)).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Rechercher dans l’annuaire' })).not.toBeInTheDocument()
  })

  it('says it searches while the directory has not answered', async () => {
    const user = renderTheSection(anOrganization())
    theApi.mockReturnValue(new Promise(() => {}))

    await openTheIdentity(user)
    await user.click(screen.getByRole('button', { name: 'Rechercher dans l’annuaire' }))

    expect(await screen.findByText('Recherche dans l’annuaire…')).toBeInTheDocument()
  })

  it('opens the identity with its SIRET, optional', async () => {
    const user = renderTheSection(
      anOrganization({ legalIdentity: { corporateName: 'ACME', siret: A_SIRET } }),
    )

    await openTheIdentity(user)

    expect(screen.getByLabelText(/^SIRET\s?\(facultatif\)$/)).toHaveValue(A_SIRET)
  })

  it('tells the server out of reach when the search cannot reach it', async () => {
    const user = renderTheSection(anOrganization())
    theApi.mockRejectedValue(new TypeError('Failed to fetch'))

    await openTheIdentity(user)
    await user.click(screen.getByRole('button', { name: 'Rechercher dans l’annuaire' }))

    expect(await screen.findByRole('alert')).toHaveTextContent(
      'Impossible de joindre le serveur. Vérifiez votre connexion.',
    )
  })
})
