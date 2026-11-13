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

/** The address written on one line, as a place to go to. */
export const placeOf = (address: Address | null | undefined): string => addressLines(address).join(', ')

/** A place by its position, or by its address written freely, which OpenStreetMap searches as it is. */
export type Destination = Location | string

const coordinates = ({ latitude, longitude }: Location) => `${latitude},${longitude}`

const positionOf = (destination: Destination) =>
  typeof destination === 'string' ? destination : coordinates(destination)

/** The route to a place on OpenStreetMap, from another one when known. */
export function directionsUrl(to: Destination, from?: Location | null): string {
  const route = new URLSearchParams({ ...(from ? { from: coordinates(from) } : {}), to: positionOf(to) })

  return `https://www.openstreetmap.org/directions?${route}`
}
