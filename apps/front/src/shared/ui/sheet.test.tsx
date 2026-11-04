import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { Sheet, SheetContent, SheetDescription, SheetTitle } from './sheet'

describe('SheetContent', () => {
  it('names its cross « Fermer », in the language of the page', () => {
    render(
      <Sheet open>
        <SheetContent>
          <SheetTitle>Menu</SheetTitle>
          <SheetDescription>Les pages de Prospecter.</SheetDescription>
        </SheetContent>
      </Sheet>,
    )

    expect(screen.getByRole('button', { name: 'Fermer' })).toBeInTheDocument()
  })
})
