import { QueryClient, QueryClientProvider, useSuspenseQuery } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Suspense } from 'react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { TooltipProvider } from '@/shared/ui/tooltip'
import { type Organization, organizationQuery } from '../queries'
import { AddressSection } from './address-section'

const theApi = vi.hoisted(() => {
  const fetch = vi.fn<typeof globalThis.fetch>()
  globalThis.fetch = fetch
  return fetch
})

vi.mock('@/shared/auth/session', () => ({
  session: { accessToken: async () => null, renewedAccessToken: async () => null },
}))

vi.mock('@/shared/ui/map/geo-map', () => ({
  GeoMap: ({ markers }: { markers: { label: string }[] }) => (
    <figure aria-label={`Carte de ${markers[0]?.label}`} />
  ),
}))

const BABBAGE = '0190a1c0-0000-7000-8000-000000000001'
const unknownAddress = { street: '12 rue Imaginaire', postalCode: '99999', city: 'Nulle-Part', country: 'FR' }
const strasbourg = { street: '1 place Kléber', postalCode: '67000', city: 'Strasbourg', country: 'FR' }
const kleber = { latitude: 48.5834, longitude: 7.7456 }
const UNLOCATED = 'Non géolocalisée'

const anOrganization = (organization: Partial<Organization>): Organization => ({
  id: BABBAGE,
  name: 'Babbage & Co',
  legalIdentity: { corporateName: 'Babbage & Co' },
  contactDetails: {},
  contactIds: [],
  createdAt: '2026-10-15T12:30:00+00:00',
  modifiedAt: '2026-10-15T12:30:00+00:00',
  _links: {
    self: { href: `/api/organizations/${BABBAGE}`, method: 'GET' },
    relocate: { href: `/api/organizations/${BABBAGE}/address`, method: 'PATCH' },
  },
  ...organization,
})

function TheAddressOfTheOrganization() {
  const { data: organization } = useSuspenseQuery(organizationQuery(BABBAGE))

  return <AddressSection organization={organization} />
}

function renderTheSection(organization: Organization) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: Number.POSITIVE_INFINITY } },
  })
  queryClient.setQueryData(organizationQuery(BABBAGE).queryKey, organization)
  render(
    <QueryClientProvider client={queryClient}>
      <TooltipProvider>
        <Suspense>
          <TheAddressOfTheOrganization />
        </Suspense>
      </TooltipProvider>
    </QueryClientProvider>,
  )
  return userEvent.setup()
}

function givenTheApiRelocatesItTo(relocated: Organization) {
  theApi.mockResolvedValue(Response.json(relocated))
}

async function relocateTo(user: ReturnType<typeof userEvent.setup>, address: typeof strasbourg) {
  await user.click(screen.getByRole('button', { name: 'Modifier : Adresse' }))
  await user.clear(screen.getByLabelText('Rue'))
  await user.type(screen.getByLabelText('Rue'), address.street)
  await user.clear(screen.getByLabelText('Code postal'))
  await user.type(screen.getByLabelText('Code postal'), address.postalCode)
  await user.clear(screen.getByLabelText('Ville'))
  await user.type(screen.getByLabelText('Ville'), address.city)
  await user.click(screen.getByRole('button', { name: 'Enregistrer' }))
}

afterEach(() => {
  theApi.mockReset()
})

describe('AddressSection', () => {
  it('marks an address the geocoder did not recognize, and says why', async () => {
    const user = renderTheSection(anOrganization({ address: unknownAddress, location: null }))

    await user.click(screen.getByRole('button', { name: UNLOCATED }))

    expect(await screen.findByRole('tooltip')).toHaveTextContent(
      'Adresse introuvable sur la carte : vérifiez-la pour la voir sur la carte et dans les itinéraires.',
    )
    expect(screen.getByText('Adresse non géolocalisée.')).toBeInTheDocument()
  })

  it('marks nothing without an address', () => {
    renderTheSection(anOrganization({ address: null, location: null }))

    expect(screen.getByText('Non renseigné')).toBeInTheDocument()
    expect(screen.queryByText(UNLOCATED)).not.toBeInTheDocument()
  })

  it('marks nothing on a geolocated address', () => {
    renderTheSection(anOrganization({ address: strasbourg, location: kleber }))

    expect(screen.queryByText(UNLOCATED)).not.toBeInTheDocument()
  })

  it('takes the mark off once the address is corrected, and shows the map', async () => {
    const user = renderTheSection(anOrganization({ address: unknownAddress, location: null }))
    givenTheApiRelocatesItTo(anOrganization({ address: strasbourg, location: kleber }))

    await relocateTo(user, strasbourg)

    expect(await screen.findByRole('figure', { name: 'Carte de Babbage & Co' })).toBeInTheDocument()
    expect(screen.queryByText(UNLOCATED)).not.toBeInTheDocument()
  })

  it('marks an address the geocoder does not recognize as soon as it is saved', async () => {
    const user = renderTheSection(anOrganization({ address: strasbourg, location: kleber }))
    givenTheApiRelocatesItTo(anOrganization({ address: unknownAddress, location: null }))

    await relocateTo(user, unknownAddress)

    expect(await screen.findByRole('button', { name: UNLOCATED })).toBeInTheDocument()
  })
})
