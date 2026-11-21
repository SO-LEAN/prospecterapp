import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { ExternalLinks } from './external-links'

const wttj = {
  source: 'welcomeToTheJungle',
  identifier: 'acme',
  address: 'https://www.welcometothejungle.com/fr/companies/acme',
}
const directory = {
  source: 'annuaire-xyz.com',
  identifier: 'annuaire-xyz.com/fiche/acme',
  address: 'https://annuaire-xyz.com/fiche/acme',
}

describe('the links of a sheet', () => {
  it('open each address in a new tab, without its scheme nor www', () => {
    render(<ExternalLinks links={[wttj]} masked={false} />)

    const link = screen.getByRole('link', { name: /welcometothejungle\.com\/fr\/companies\/acme$/ })
    expect(link).toHaveAttribute('href', wttj.address)
    expect(link).toHaveAttribute('target', '_blank')
  })

  it.each([
    ['linkedInProfile', 'LinkedIn'],
    ['linkedInCompany', 'LinkedIn'],
    ['malt', 'Malt'],
    ['welcomeToTheJungle', 'Welcome to the Jungle'],
  ])('show the mark of a known source, %s', (source, name) => {
    render(<ExternalLinks links={[{ ...wttj, source }]} masked={false} />)

    expect(screen.getByRole('img', { name })).toBeInTheDocument()
  })

  it('show a web mark for a source it does not know', () => {
    render(<ExternalLinks links={[directory]} masked={false} />)

    expect(screen.getByRole('img', { name: 'Site web' })).toBeInTheDocument()
  })

  it('read masked, show their stars, no link to follow', () => {
    render(<ExternalLinks links={[{ source: '***', identifier: '***', address: '***' }]} masked />)

    expect(screen.getByText('***')).toBeInTheDocument()
    expect(screen.queryByRole('link')).not.toBeInTheDocument()
  })
})
