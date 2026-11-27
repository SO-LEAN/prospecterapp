import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { MarkerPin } from './marker-pin'

describe('MarkerPin', () => {
  it('shows the initials of its label, letters only', () => {
    render(<MarkerPin label="[TEST] Filon Audit SARL" />)

    expect(screen.getByText('FA')).toBeInTheDocument()
  })

  it('shows its image instead of the initials when it has one', () => {
    const { container } = render(
      <MarkerPin label="Filon Audit" imageUrl="https://static.example/filon.png" />,
    )

    expect(container.querySelector('img')).toHaveAttribute('src', 'https://static.example/filon.png')
    expect(screen.queryByText('FA')).not.toBeInTheDocument()
  })
})
