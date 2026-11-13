import { describe, expect, it } from 'vitest'
import { addressLines, directionsUrl, placeOf } from './address'

const strasbourg = { latitude: 48.5834, longitude: 7.7456 }
const colmar = { latitude: 48.0794, longitude: 7.3585 }

describe('directionsUrl', () => {
  it('starts from the place given', () => {
    expect(directionsUrl(colmar, strasbourg)).toBe(
      'https://www.openstreetmap.org/directions?from=48.5834%2C7.7456&to=48.0794%2C7.3585',
    )
  })

  it('leaves the start to the visitor without one', () => {
    expect(directionsUrl(colmar, null)).toBe('https://www.openstreetmap.org/directions?to=48.0794%2C7.3585')
  })

  it('goes to an address written freely, searched as it is', () => {
    expect(directionsUrl('Café de Flore, Paris', strasbourg)).toBe(
      'https://www.openstreetmap.org/directions?from=48.5834%2C7.7456&to=Caf%C3%A9+de+Flore%2C+Paris',
    )
  })
})

describe('placeOf', () => {
  it('writes an address on one line', () => {
    expect(placeOf({ street: '12 rue de la Paix', postalCode: '75002', city: 'Paris', country: null })).toBe(
      '12 rue de la Paix, 75002 Paris',
    )
  })

  it('is empty without an address', () => {
    expect(placeOf(null)).toBe('')
  })
})

describe('addressLines', () => {
  it('writes the parts given, the country by its name', () => {
    expect(addressLines({ street: null, postalCode: '68000', city: 'Colmar', country: 'FR' })).toEqual([
      '68000 Colmar',
      'France',
    ])
  })
})
