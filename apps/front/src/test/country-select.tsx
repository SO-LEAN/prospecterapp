import type { ComponentProps } from 'react'
import { frequentCountries, otherCountries } from '@/shared/lib/countries'
import type { CountrySelect as TheCountrySelect } from '@/shared/ui/form/country-select'

const NONE = ''

/**
 * The country select as a native select: the one of Radix mounts its 249 options even closed, a second
 * or more per form in jsdom. For the tests of a form that does not choose a country:
 * `vi.mock('@/shared/ui/form/country-select', () => import('@/test/country-select'))`.
 */
export function CountrySelect({
  value,
  onChange,
  placeholder,
  ...aria
}: ComponentProps<typeof TheCountrySelect>) {
  return (
    <select
      value={value ?? NONE}
      onChange={(event) => onChange(event.target.value === NONE ? null : event.target.value)}
      {...aria}
    >
      <option value={NONE}>{placeholder ?? '—'}</option>
      {[...frequentCountries, ...otherCountries].map((country) => (
        <option key={country.code} value={country.code}>
          {country.name}
        </option>
      ))}
    </select>
  )
}
