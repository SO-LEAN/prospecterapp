import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { CountrySelect } from './country-select'

const noCountry = 'Aucun pays'

function theCountries(value: string | null) {
  const onChange = vi.fn<(country: string | null) => void>()
  render(<CountrySelect value={value} onChange={onChange} placeholder={noCountry} />)
  return onChange
}

async function choose(option: string) {
  const user = userEvent.setup()
  await user.click(screen.getByRole('combobox'))
  await user.click(screen.getByRole('option', { name: option }))
}

describe('the list of the countries gives its form the chosen country', () => {
  it('gives the code of the chosen country', async () => {
    const onChange = theCountries(null)

    await choose('Belgique')

    expect(onChange).toHaveBeenCalledExactlyOnceWith('BE')
  })

  it('gives null when the empty choice is chosen again', async () => {
    const onChange = theCountries('FR')

    await choose(noCountry)

    expect(onChange).toHaveBeenCalledExactlyOnceWith(null)
  })

  it('shows the country already chosen', () => {
    theCountries('DE')

    expect(screen.getByRole('combobox')).toHaveTextContent('Allemagne')
  })
})
