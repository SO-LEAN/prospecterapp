import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { CreatedByAnAIBadge } from './created-by-an-ai-badge'

describe('CreatedByAnAIBadge', () => {
  it('reads « IA », and « Créé par une IA » on hover and for screen readers', () => {
    render(<CreatedByAnAIBadge />)

    const badge = screen.getByTitle('Créé par une IA')
    expect(badge).toHaveTextContent(/^IA\s*Créé par une IA$/)
    expect(screen.getByText('Créé par une IA')).toHaveClass('sr-only')
  })
})
