import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import type { Account } from '../mutations'
import { MyOrganizationAddressSection } from './account-sections'

const colmar = { street: '1 rue des Clefs', postalCode: '68000', city: 'Colmar', country: 'FR' }

function anAccountOfMyOrganization(organization: Partial<Account['organization']>): Account {
  return {
    name: { firstName: 'Michel', lastName: null },
    contactDetails: { email: 'michel@example.com' },
    organization: { legalIdentity: { corporateName: 'SO-LEAN' }, ...organization },
    _links: { self: { href: '/api/me', method: 'GET' } },
  }
}

function renderTheSection(account: Account) {
  render(
    <QueryClientProvider client={new QueryClient()}>
      <MyOrganizationAddressSection account={account} />
    </QueryClientProvider>,
  )
}

describe('MyOrganizationAddressSection', () => {
  it('says an address the geocoder did not recognize is not geolocated', () => {
    renderTheSection(anAccountOfMyOrganization({ address: colmar, location: null }))

    expect(screen.getByText('Adresse non géolocalisée.')).toBeInTheDocument()
  })

  it('says nothing of the location of a geolocated address', () => {
    renderTheSection(
      anAccountOfMyOrganization({ address: colmar, location: { latitude: 48.0794, longitude: 7.3585 } }),
    )

    expect(screen.queryByText('Adresse non géolocalisée.')).not.toBeInTheDocument()
  })

  it('says nothing of the location without an address', () => {
    renderTheSection(anAccountOfMyOrganization({ address: null, location: null }))

    expect(screen.queryByText('Adresse non géolocalisée.')).not.toBeInTheDocument()
  })
})
