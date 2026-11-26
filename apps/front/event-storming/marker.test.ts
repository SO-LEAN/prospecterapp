import { describe, expect, it } from 'vitest'
import { arrowOf, braceOf, lean, wobbly } from './marker.ts'

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

  it('draws the arrow of a reaction from the right of a post-it to the left of the next, with its head', () => {
    const arrow = arrowOf(
      { x: 0, y: 0, w: 132, h: 132, seed: 'a' },
      { x: 400, y: 0, w: 132, h: 132, seed: 'b' },
      true,
    )

    expect([arrow.curve.startsWith('M135,'), arrow.head.startsWith('M391,'), arrow.maybe]).toEqual([
      true,
      true,
      null,
    ])
  })

  it('routes the arrow to a lower row down into the gap under its row, along it, then down its corridor', () => {
    const arrow = arrowOf(
      { x: 0, y: 0, w: 132, h: 132, seed: 'a' },
      { x: 400, y: 500, w: 132, h: 132, seed: 'b' },
      false,
      { out: 150, corridor: 360, drop: 150 },
    )
    const corners = [...arrow.curve.matchAll(/Q([\d.-]+),([\d.-]+)/g)].map(([, x, y]) => [
      Number(x),
      Number(y),
    ])

    expect([corners, arrow.curve.endsWith('L391,' + arrow.curve.split(',').at(-1)), arrow.maybe]).toEqual([
      [
        [150, expect.closeTo(66, -1)],
        [150, 150],
        [360, 150],
        [360, expect.closeTo(566, -1)],
      ],
      true,
      { x: (150 + 360) / 2, y: 144 },
    ])
  })

  it('writes maybe on the arrow of a reaction that happens only under a condition', () => {
    const arrow = arrowOf(
      { x: 0, y: 0, w: 132, h: 132, seed: 'a' },
      { x: 400, y: 0, w: 132, h: 132, seed: 'b' },
      false,
    )

    expect(arrow.maybe?.x).toBe((135 + 391) / 2)
  })

  it('branches the line of a view to each command it offers, a tick at each one', () => {
    const view = { x: 0, y: 0, w: 132, h: 132, seed: 'view' }
    const commands = [0, 200].map((y) => ({ x: 400, y, w: 132, h: 132, seed: `command${y}` }))

    const brace = braceOf(view, commands, 300)

    expect(
      brace.branches.map((branch) => [branch.target.split(' ')[0], branch.head.split(' ')[0]?.split(',')[0]]),
    ).toEqual([
      [expect.stringMatching(/^M300,/), 'M391'],
      [expect.stringMatching(/^M300,/), 'M391'],
    ])
  })
})
