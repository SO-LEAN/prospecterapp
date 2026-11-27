import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { Button } from './button'
import { Kbd } from './kbd'

describe('Kbd', () => {
  it('shows its key in a button, out of the button’s name', () => {
    render(
      <Button aria-keyshortcuts="L">
        Noter un échange <Kbd>L</Kbd>
      </Button>,
    )

    const button = screen.getByRole('button', { name: 'Noter un échange' })
    expect(button.querySelector('kbd')).toHaveTextContent('L')
  })

  it('draws its key as a chip, in the label’s type', () => {
    render(<Kbd>L</Kbd>)

    expect(screen.getByText('L')).toHaveClass('rounded-chip', 'text-label')
  })
})
