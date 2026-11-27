import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { Card, CardHeader, CardTitle } from './card'

describe('Card', () => {
  it('rests on the page, rounded by the card radius', () => {
    render(<Card data-testid="card" />)

    expect(screen.getByTestId('card')).toHaveClass('rounded-card', 'shadow-rest')
  })

  it('titles itself in Inter Display, in the card title', () => {
    render(
      <Card>
        <CardHeader>
          <CardTitle>À traiter</CardTitle>
        </CardHeader>
      </Card>,
    )

    expect(screen.getByText('À traiter')).toHaveClass('font-display', 'text-card-title')
  })
})
