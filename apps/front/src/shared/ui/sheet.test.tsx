import { render, screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { Sheet, SheetContent, SheetDescription, SheetTitle } from './sheet'

describe('SheetContent', () => {
  it('names its cross « Fermer », in the language of the page', () => {
    renderTheSheet('right')

    expect(screen.getByRole('button', { name: 'Fermer' })).toBeInTheDocument()
  })

  it.each([
    ['right', 'slide-in-from-right-6', 'data-[state=closed]:slide-out-to-right-6'],
    ['left', 'slide-in-from-left-6', 'data-[state=closed]:slide-out-to-left-6'],
    ['top', 'slide-in-from-top-6', 'data-[state=closed]:slide-out-to-top-6'],
    ['bottom', 'slide-in-from-bottom-6', 'data-[state=closed]:slide-out-to-bottom-6'],
  ] as const)(
    'opens from the %s in 220 ms, sliding 24 px, and leaves in 160 ms',
    (side, slidingIn, slidingOut) => {
      renderTheSheet(side)

      expect(theSlot('sheet-content')).toHaveClass(
        'animate-in',
        'duration-220',
        'ease-enter',
        'fade-in-0',
        slidingIn,
        'data-[state=closed]:duration-160',
        'data-[state=closed]:animate-out',
        'data-[state=closed]:fade-out-0',
        'data-[state=closed]:ease-exit',
        slidingOut,
      )
    },
  )

  it('blurs the page behind it by 3 px', () => {
    renderTheSheet('right')

    expect(theSlot('sheet-overlay')).toHaveClass('backdrop-blur-[3px]')
  })

  it('stands lifted over the page', () => {
    renderTheSheet('right')

    expect(theSlot('sheet-content')).toHaveClass('shadow-lift')
  })
})

function renderTheSheet(side: 'top' | 'right' | 'bottom' | 'left') {
  render(
    <Sheet open>
      <SheetContent side={side}>
        <SheetTitle>Menu</SheetTitle>
        <SheetDescription>Les pages de Prospecter.</SheetDescription>
      </SheetContent>
    </Sheet>,
  )
}

function theSlot(slot: string) {
  return document.querySelector(`[data-slot="${slot}"]`)
}
