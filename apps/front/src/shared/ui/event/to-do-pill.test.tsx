import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { ToDoPill } from './to-do-pill'

const thePill = () => screen.getByText('Relancer').closest('[data-slot="to-do-pill"]')

describe('ToDoPill', () => {
  it('draws a to-do still to do as a box with an amber edge', () => {
    render(<ToDoPill done={false}>Relancer</ToDoPill>)

    expect(thePill()).toHaveClass('border', 'border-event-todo')
    expect(screen.getByRole('img', { name: 'À faire' }).querySelector('.lucide-square')).toBeInTheDocument()
  })

  it('lays a to-do out as a small rounded row, as an exchange', () => {
    render(<ToDoPill done={false}>Relancer</ToDoPill>)

    expect(thePill()).toHaveClass('flex', 'items-center', 'rounded-md', 'px-1.5', 'text-xs')
  })

  it('fills a done to-do, its text faded and struck through', () => {
    render(<ToDoPill done>Relancer</ToDoPill>)

    expect(thePill()).toHaveClass('bg-event-todo-soft', 'text-muted-foreground', 'line-through')
    expect(thePill()).not.toHaveClass('border-event-todo')
    expect(
      screen.getByRole('img', { name: 'Faite' }).querySelector('.lucide-square-check'),
    ).toBeInTheDocument()
  })

  it('edges a late to-do in red, and says it is late', () => {
    render(
      <ToDoPill done={false} late>
        Relancer
      </ToDoPill>,
    )

    expect(thePill()).toHaveClass('border-l-4', 'border-l-destructive', 'border-event-todo')
    expect(screen.getByRole('img', { name: 'En retard' })).toBeInTheDocument()
  })
})
