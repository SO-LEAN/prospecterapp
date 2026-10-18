import { describe, expect, it } from 'vitest'
import { countryName, frequentCountries, otherCountries } from './countries'

const listed = [...frequentCountries, ...otherCountries]
const names = (countries: { name: string }[]) => countries.map((country) => country.name)

describe('the countries of the list', () => {
  it('are the 249 countries of ISO 3166-1, each listed once', () => {
    expect(new Set(listed.map((country) => country.code)).size).toBe(249)
    expect(listed).toHaveLength(249)
  })

  it('never show a name twice', () => {
    expect(new Set(names(listed)).size).toBe(listed.length)
  })

  it('leave out the codes withdrawn from the standard or outside it', () => {
    const codes = listed.map((country) => country.code)
    for (const code of ['FX', 'DD', 'UK', 'SU', 'YU', 'CS', 'ZR', 'AN', 'XK', 'IC', 'EU']) {
      expect(codes).not.toContain(code)
    }
  })

  it('show France first, among the frequent ones only', () => {
    expect(frequentCountries[0]).toEqual({ code: 'FR', name: 'France' })
    expect(names(otherCountries)).not.toContain('France')
  })

  it('sort the others by their French name', () => {
    const sorted = [...names(otherCountries)].sort((a, b) => a.localeCompare(b, 'fr'))
    expect(names(otherCountries)).toEqual(sorted)
  })
})

describe('countryName', () => {
  it('names a country kept by its code', () => {
    expect(countryName('FR')).toBe('France')
  })
})
