import { render } from '@testing-library/react'
import type { ReactNode } from 'react'
import { describe, expect, it } from 'vitest'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
  DropdownMenuTrigger,
} from './dropdown-menu'
import { Popover, PopoverContent, PopoverTrigger } from './popover'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './select'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from './tooltip'

/** What floats over the page, opened, by the slot of its content. */
const FLOATING: [string, ReactNode][] = [
  [
    'dropdown-menu-content',
    <DropdownMenu key="menu" open modal={false}>
      <DropdownMenuTrigger>Actions</DropdownMenuTrigger>
      <DropdownMenuContent>
        <DropdownMenuItem>Reporter</DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>,
  ],
  [
    'dropdown-menu-sub-content',
    <DropdownMenu key="sub-menu" open modal={false}>
      <DropdownMenuTrigger>Actions</DropdownMenuTrigger>
      <DropdownMenuContent>
        <DropdownMenuSub open>
          <DropdownMenuSubTrigger>Déplacer</DropdownMenuSubTrigger>
          <DropdownMenuSubContent>
            <DropdownMenuItem>Demain</DropdownMenuItem>
          </DropdownMenuSubContent>
        </DropdownMenuSub>
      </DropdownMenuContent>
    </DropdownMenu>,
  ],
  [
    'popover-content',
    <Popover key="popover" open>
      <PopoverTrigger>Filtres</PopoverTrigger>
      <PopoverContent>Les étiquettes</PopoverContent>
    </Popover>,
  ],
  [
    'select-content',
    <Select key="select" open>
      <SelectTrigger aria-label="Pays">
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="FR">France</SelectItem>
      </SelectContent>
    </Select>,
  ],
  [
    'tooltip-content',
    <TooltipProvider key="tooltip">
      <Tooltip open>
        <TooltipTrigger>Aide</TooltipTrigger>
        <TooltipContent>Ce qui est à traiter</TooltipContent>
      </Tooltip>
    </TooltipProvider>,
  ],
]

function opened(slot: string, floating: ReactNode) {
  render(floating)
  return document.querySelector(`[data-slot="${slot}"]`)
}

describe('what floats over the page', () => {
  it.each(FLOATING)('opens a %s in 160 ms, fading in and growing from 0.97', (slot, floating) => {
    const content = opened(slot, floating)

    expect(content).toHaveClass('duration-160', 'ease-enter', 'fade-in-0', 'zoom-in-97')
    expect(content?.className).not.toMatch(/slide-in-from/)
  })

  it.each(FLOATING)('closes a %s in 160 ms, fading out and shrinking to 0.97', (slot, floating) => {
    const content = opened(slot, floating)

    expect(content).toHaveClass('data-[state=closed]:ease-exit', 'data-[state=closed]:zoom-out-97')
  })

  it.each(FLOATING.filter(([slot]) => slot !== 'tooltip-content'))(
    'floats a %s on the second shadow level',
    (slot, floating) => {
      expect(opened(slot, floating)).toHaveClass('shadow-float')
    },
  )
})
