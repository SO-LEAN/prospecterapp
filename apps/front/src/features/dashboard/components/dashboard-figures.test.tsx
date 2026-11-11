import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { DashboardFigures } from './dashboard-figures'

const NARROW_NO_BREAK_SPACE = ' '

function figureOf(label: string) {
  return screen.getByText(label).previousElementSibling?.textContent
}

describe('the figures of the dashboard', () => {
  it('shows each figure of my organization under its label', () => {
    render(
      <DashboardFigures
        stats={{
          activeOrganizations: 1200,
          locatedOrganizations: 340,
          contacts: 56,
          exchanges: 78,
          exchangesThisMonth: 9,
        }}
      />,
    )

    expect(figureOf('Organisations actives')).toBe(`1${NARROW_NO_BREAK_SPACE}200`)
    expect(figureOf('Organisations géolocalisées')).toBe('340')
    expect(figureOf('Contacts')).toBe('56')
    expect(figureOf('Échanges notés')).toBe('78')
    expect(figureOf('Échanges ce mois-ci')).toBe('9')
  })
})
