import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderInARouter } from '@/test/render-in-a-router'
import type { DashboardStats } from '../queries'
import { DashboardFigures } from './dashboard-figures'

const NARROW_NO_BREAK_SPACE = '\u202f'
const NO_BREAK_SPACE = '\u00a0'

const myStats: DashboardStats = {
  activeOrganizations: 1200,
  locatedOrganizations: 340,
  contacts: 56,
  exchanges: 78,
  exchangesThisMonth: 9,
}

function figureOf(label: string) {
  return screen.getByText(label).previousElementSibling?.textContent
}

describe('the figures of the dashboard', () => {
  it('shows each figure of my organization under its label', async () => {
    await renderInARouter(<DashboardFigures stats={myStats} />)

    expect(figureOf('Organisations actives')).toBe(`1${NARROW_NO_BREAK_SPACE}200`)
    expect(figureOf('Organisations géolocalisées')).toBe('340')
    expect(figureOf('Contacts')).toBe('56')
    expect(figureOf('Échanges notés')).toBe('78')
    expect(figureOf('Échanges ce mois-ci')).toBe('9')
  })

  it('leads from the active organizations to their list, its name saying so', async () => {
    await renderInARouter(<DashboardFigures stats={myStats} />)

    expect(
      screen.getByRole('link', {
        name: `1${NARROW_NO_BREAK_SPACE}200 Organisations actives${NO_BREAK_SPACE}: voir la liste des organisations`,
      }),
    ).toHaveAttribute('href', '/organizations')
  })

  it("leads from the located organizations to the dashboard's map", async () => {
    await renderInARouter(<DashboardFigures stats={myStats} />)

    expect(
      screen.getByRole('link', {
        name: `340 Organisations géolocalisées${NO_BREAK_SPACE}: voir la carte des organisations`,
      }),
    ).toHaveAttribute('href', '/#organizations-map')
  })

  it('leads from the contacts to their list', async () => {
    await renderInARouter(<DashboardFigures stats={myStats} />)

    expect(
      screen.getByRole('link', { name: `56 Contacts${NO_BREAK_SPACE}: voir la liste des contacts` }),
    ).toHaveAttribute('href', '/contacts')
  })

  it("leads from this month's exchanges to the calendar, on today's month", async () => {
    await renderInARouter(<DashboardFigures stats={myStats} />)

    expect(
      screen.getByRole('link', {
        name: `9 Échanges ce mois-ci${NO_BREAK_SPACE}: voir le calendrier du mois`,
      }),
    ).toHaveAttribute('href', '/calendar?view=month')
  })

  it('keeps the logged exchanges a plain figure, since no list holds them', async () => {
    await renderInARouter(<DashboardFigures stats={myStats} />)

    expect(screen.getByText('Échanges notés').closest('a')).toBeNull()
    expect(screen.getAllByRole('link')).toHaveLength(4)
  })
})
