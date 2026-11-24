import { describe, expect, it } from 'vitest'
import { lean, wobbly } from './marker.ts'

describe('the marker of the wall', () => {
  it('leans a post-it the same way for the same name, at each generation', () => {
    expect(lean('command:BookSlot', 2.3)).toBe(lean('command:BookSlot', 2.3))
  })

  it('leans a post-it no more than its amplitude', () => {
    const leans = ['command:BookSlot', 'event:SlotBooked', 'actor:public'].map((name) => lean(name, 2.3))

    expect(leans.every((value) => Math.abs(value) <= 2.3)).toBe(true)
  })

  it('draws a marker line from where it starts to where it ends', () => {
    const line = wobbly({ x: 10, y: 20 }, { x: 600, y: 20 }, 'rule')

    expect([line.split(' ')[0], line.split(' ').at(-1)?.split(',')[0]]).toEqual(['M10,20', '600.0'])
  })
})
