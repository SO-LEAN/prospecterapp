import { describe, expect, it } from 'vitest'
import { linesOf, widthOf } from './ink.ts'

describe('the ink of a post-it', () => {
  it('measures a handwritten text by the advance of each of its letters', () => {
    expect(widthOf('mi', 20, 'hand')).toBeCloseTo((0.68 + 0.224) * 20)
  })

  it('wraps a label at its spaces to fit the post-it', () => {
    expect(linesOf('Forget appointment of erased exchange', 18.5, 'hand', 110)).toEqual([
      'Forget',
      'appointment of',
      'erased exchange',
    ])
  })

  it('wraps a name too long for a line at its capitals', () => {
    expect(linesOf('addInactivePeriod()', 10.5, 'mono', 78)).toEqual(['addInactive', 'Period()'])
  })

  it('cuts a word that holds no capital where the line ends', () => {
    const lines = linesOf('abcdefghijklmnopqrstuvwxyz', 10, 'mono', 62)

    expect([lines.join(''), lines.every((line) => widthOf(line, 10, 'mono') <= 62)]).toEqual([
      'abcdefghijklmnopqrstuvwxyz',
      true,
    ])
  })
})
