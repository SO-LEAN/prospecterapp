import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { CivilityMark } from './civility-mark'
import { TooltipProvider } from './tooltip'

function renderTheMark(civility: 'ms' | 'mr' | 'x') {
  render(
    <TooltipProvider>
      <CivilityMark civility={civility} />
    </TooltipProvider>,
  )
  return userEvent.setup()
}

describe('the mark of the civility of a contact', () => {
  it('is named Madame, and says so on hover', async () => {
    const user = renderTheMark('ms')

    await user.hover(screen.getByRole('img', { name: 'Madame' }))

    expect(await screen.findByRole('tooltip')).toHaveTextContent('Madame')
  })

  it('is named Monsieur, and says so on hover', async () => {
    const user = renderTheMark('mr')

    await user.hover(screen.getByRole('img', { name: 'Monsieur' }))

    expect(await screen.findByRole('tooltip')).toHaveTextContent('Monsieur')
  })

  it('shows nothing when the civility is unknown', () => {
    renderTheMark('x')

    expect(screen.queryByRole('img')).not.toBeInTheDocument()
  })
})
