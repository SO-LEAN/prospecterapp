import { createMemoryHistory, createRootRoute, createRouter, RouterProvider } from '@tanstack/react-router'
import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import type { OrganizationSummary } from '../queries'
import { OrganizationCard } from './organization-card'

const UNLOCATED = 'Non géolocalisée'

const aSummary = (organization: Partial<OrganizationSummary>): OrganizationSummary => ({
  id: '0190a1c0-0000-7000-8000-000000000001',
  name: 'Babbage & Co',
  ...organization,
})

async function renderTheCards(...organizations: OrganizationSummary[]) {
  const rootRoute = createRootRoute({
    component: () => (
      <ul>
        {organizations.map((organization) => (
          <li key={organization.id}>
            <OrganizationCard organization={organization} />
          </li>
        ))}
      </ul>
    ),
  })
  render(<RouterProvider router={createRouter({ routeTree: rootRoute, history: createMemoryHistory() })} />)
  await screen.findAllByRole('link')
}

describe('OrganizationCard', () => {
  it('marks the organization whose address is not geolocated, not the others', async () => {
    await renderTheCards(
      aSummary({ postalCode: '99999', city: 'Nulle-Part', country: 'FR', location: null }),
      aSummary({
        id: '0190a1c0-0000-7000-8000-000000000002',
        name: 'Lovelace SA',
        postalCode: '67000',
        city: 'Strasbourg',
        country: 'FR',
        location: { latitude: 48.5834, longitude: 7.7456 },
      }),
    )

    expect(screen.getByRole('link', { name: /Babbage & Co/ })).toHaveTextContent(UNLOCATED)
    expect(screen.getByRole('link', { name: /Lovelace SA/ })).not.toHaveTextContent(UNLOCATED)
  })

  it('marks nothing without an address', async () => {
    await renderTheCards(aSummary({ location: null }))

    expect(screen.queryByText(UNLOCATED)).not.toBeInTheDocument()
  })
})
