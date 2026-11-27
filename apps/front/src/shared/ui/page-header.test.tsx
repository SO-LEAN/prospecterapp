import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { PageHeader } from './page-header'

describe('PageHeader', () => {
  it('titles the page in Inter Display, at the title size', () => {
    render(<PageHeader title="Bonjour Michel" />)

    expect(screen.getByRole('heading', { level: 1, name: 'Bonjour Michel' })).toHaveClass(
      'font-display',
      'text-title',
    )
  })
})
