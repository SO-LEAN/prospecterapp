import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { TooltipProvider } from './tooltip'
import { ExplainedUnlocatedBadge } from './unlocated-badge'

const EXPLANATION =
  'Adresse introuvable sur la carte : vérifiez-la pour la voir sur la carte et dans les itinéraires.'

function renderTheBadge() {
  render(
    <TooltipProvider>
      <ExplainedUnlocatedBadge />
    </TooltipProvider>,
  )
  return userEvent.setup()
}

describe('ExplainedUnlocatedBadge', () => {
  it('explains itself to the keyboard', async () => {
    const user = renderTheBadge()

    await user.tab()

    expect(screen.getByRole('button', { name: 'Non géolocalisée' })).toHaveFocus()
    expect(await screen.findByRole('tooltip')).toHaveTextContent(EXPLANATION)
  })

  it('explains itself when pressed, as on a phone', async () => {
    const user = renderTheBadge()

    await user.click(screen.getByRole('button', { name: 'Non géolocalisée' }))

    expect(await screen.findByRole('tooltip')).toHaveTextContent(EXPLANATION)
  })
})
