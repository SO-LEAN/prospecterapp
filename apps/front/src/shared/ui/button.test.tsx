import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { Button } from './button'

const SIZES = ['default', 'xs', 'sm', 'lg', 'icon', 'icon-xs', 'icon-sm', 'icon-lg'] as const

describe('Button', () => {
  it.each(SIZES)('rounds a %s button by the button radius', (size) => {
    render(<Button size={size}>Enregistrer</Button>)

    expect(screen.getByRole('button')).toHaveClass('rounded-button')
  })

  it('answers the hover in 120 ms', () => {
    render(<Button>Enregistrer</Button>)

    expect(screen.getByRole('button')).toHaveClass('duration-120', 'ease-standard')
  })
})
