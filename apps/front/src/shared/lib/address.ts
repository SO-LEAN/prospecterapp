import type { components } from '@/shared/api/schema'
import { countryName } from './countries'

type Address = components['schemas']['AddressResource']
type Location = components['schemas']['LocationResource']

/** The lines an address is written on: street, postal code and city, country. */
export function addressLines(address: Address | null | undefined): string[] {
  if (!address) {
    return []
  }
  return [
    address.street,
    [address.postalCode, address.city].filter(Boolean).join(' '),
    countryName(address.country),
  ].filter((line): line is string => Boolean(line))
}

const coordinates = ({ latitude, longitude }: Location) => `${latitude},${longitude}`

/** The route to a place on OpenStreetMap, from another one when known. */
export function directionsUrl(to: Location, from?: Location | null): string {
  const route = new URLSearchParams({ ...(from ? { from: coordinates(from) } : {}), to: coordinates(to) })

  return `https://www.openstreetmap.org/directions?${route}`
}
