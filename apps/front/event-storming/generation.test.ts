import { describe, expect, it } from 'vitest'
import { generationOf, staleSvgsOf } from './generation.ts'
import type { Document, Model } from './model.ts'

function modelOf(context: string): Model {
  return { context, modules: [], stickies: [], links: [] }
}

const DOCUMENT: Document = { models: [modelOf('Booking'), modelOf('PersonalData')], warnings: [] }

describe('what a generation writes', () => {
  it('writes every context wall when no context is named', () => {
    expect(generationOf(DOCUMENT).walls.map((wall) => wall.model.context)).toEqual([
      'Booking',
      'PersonalData',
    ])
  })

  it('writes only the wall of the context it is given', () => {
    expect(generationOf(DOCUMENT, 'PersonalData').walls.map((wall) => wall.model.context)).toEqual([
      'PersonalData',
    ])
  })

  it('refuses a context the code does not hold', () => {
    expect(() => generationOf(DOCUMENT, 'Shop')).toThrow('No context Shop in the code')
  })

  it('writes the overview when it writes every wall', () => {
    expect(generationOf(DOCUMENT).overview).toBe(true)
  })

  it('writes no overview when it writes one wall', () => {
    expect(generationOf(DOCUMENT, 'Booking').overview).toBe(false)
  })

  it('writes each wall in the folder named after its context', () => {
    expect(generationOf(DOCUMENT).walls.map((wall) => wall.folder)).toEqual(['booking', 'personal-data'])
  })

  it('removes the SVGs of a wall that it no longer writes, and nothing else', () => {
    expect(staleSvgsOf(['wall.svg', 'gone.svg', 'notes.md'], ['wall.svg', 'new.svg'])).toEqual(['gone.svg'])
  })
})
