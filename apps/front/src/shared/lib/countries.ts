import { countryNames } from '@/shared/i18n'

const frequent = ['FR', 'BE', 'CH', 'LU', 'DE', 'GB', 'ES', 'IT']

function allRegionCodes(): string[] {
  const names = new Intl.DisplayNames(['fr'], { type: 'region', fallback: 'none' })
  const codes: string[] = []
  for (let first = 65; first <= 90; first++) {
    for (let second = 65; second <= 90; second++) {
      const code = String.fromCharCode(first, second)
      if (names.of(code) && !['EU', 'EZ', 'UN', 'QO', 'XA', 'XB', 'ZZ'].includes(code)) {
        codes.push(code)
      }
    }
  }
  return codes
}

export type Country = { code: string; name: string }

/** ISO 3166 countries named in French, frequent ones first. Computed once. */
export const countries: Country[] = (() => {
  const all = allRegionCodes().map((code) => ({ code, name: countryNames.of(code) ?? code }))
  const top = frequent.flatMap((code) => all.filter((country) => country.code === code))
  const rest = all
    .filter((country) => !frequent.includes(country.code))
    .sort((a, b) => a.name.localeCompare(b.name, 'fr'))
  return [...top, ...rest]
})()

export function countryName(code: string | null | undefined): string | null {
  return code ? (countryNames.of(code) ?? code) : null
}
