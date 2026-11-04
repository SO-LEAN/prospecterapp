import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from './dialog'

describe('Dialog', () => {
  it('names its cross and its closing button « Fermer », in the language of the page', () => {
    render(
      <Dialog open>
        <DialogContent>
          <DialogTitle>Noter un échange</DialogTitle>
          <DialogDescription>Ce qui s'est dit.</DialogDescription>
          <DialogFooter showCloseButton />
        </DialogContent>
      </Dialog>,
    )

    expect(screen.getAllByRole('button', { name: 'Fermer' })).toHaveLength(2)
  })
})
