import { describe, expect, it } from 'vitest'
import { cn } from './utils'

describe('cn', () => {
  it.each(['title', 'card-title', 'body', 'label'])('keeps the text-%s size beside a text color', (size) => {
    expect(cn(`text-${size}`, 'text-muted-foreground')).toBe(`text-${size} text-muted-foreground`)
  })

  it('lets a later size replace a Filon size', () => {
    expect(cn('text-card-title', 'text-lg')).toBe('text-lg')
  })

  it.each(['chip', 'button', 'field', 'row', 'card', 'dialog'])(
    'lets a later radius replace the %s radius',
    (radius) => {
      expect(cn(`rounded-${radius}`, 'rounded-full')).toBe('rounded-full')
    },
  )

  it.each(['rest', 'float', 'lift', 'card'])('lets a later shadow replace the %s shadow', (shadow) => {
    expect(cn(`shadow-${shadow}`, 'shadow-none')).toBe('shadow-none')
  })

  it.each(['standard', 'enter', 'exit'])('lets a later easing replace the %s easing', (curve) => {
    expect(cn(`ease-${curve}`, 'ease-linear')).toBe('ease-linear')
  })
})
