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

  it('opens in 220 ms, sliding 24 px up, and leaves in 160 ms', () => {
    renderTheDialog()

    expect(theSlot('dialog-content')).toHaveClass(
      'duration-220',
      'ease-enter',
      'fade-in-0',
      'slide-in-from-bottom-6',
      'data-[state=closed]:duration-160',
      'data-[state=closed]:ease-exit',
      'data-[state=closed]:slide-out-to-bottom-6',
    )
    expect(theSlot('dialog-content')?.className).not.toMatch(/zoom-/)
  })

  it('blurs the page behind it by 3 px, at the same pace', () => {
    renderTheDialog()

    expect(theSlot('dialog-overlay')).toHaveClass(
      'backdrop-blur-[3px]',
      'duration-220',
      'data-[state=closed]:duration-160',
    )
  })

  it('stands lifted over the page, rounded by the dialog radius', () => {
    renderTheDialog()

    expect(theSlot('dialog-content')).toHaveClass('rounded-dialog', 'shadow-lift')
  })

  it('titles itself in Inter Display', () => {
    renderTheDialog()

    expect(screen.getByRole('heading', { name: 'Noter un échange' })).toHaveClass('font-display')
  })
})

function renderTheDialog() {
  render(
    <Dialog open>
      <DialogContent>
        <DialogTitle>Noter un échange</DialogTitle>
        <DialogDescription>Ce qui s'est dit.</DialogDescription>
      </DialogContent>
    </Dialog>,
  )
}

function theSlot(slot: string) {
  return document.querySelector(`[data-slot="${slot}"]`)
}
