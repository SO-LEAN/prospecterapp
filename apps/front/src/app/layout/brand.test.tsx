import { render } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { BrandMark } from './brand'

function drawnMark() {
  const { container } = render(<BrandMark />)
  return container.querySelector('svg') as SVGSVGElement
}

describe('BrandMark', () => {
  it('draws a petrol tile', () => {
    const tile = drawnMark().querySelector('rect')

    expect(tile).toHaveAttribute('fill', '#0E7C84')
  })

  it('draws a gold nugget in the top right corner', () => {
    const nugget = drawnMark().querySelector('circle')

    expect(nugget).toHaveAttribute('fill', '#F5B32E')
    expect(nugget).toHaveAttribute('cx', '46')
    expect(nugget).toHaveAttribute('cy', '16.5')
  })

  it('draws a white vein that climbs towards the nugget', () => {
    const vein = drawnMark().querySelector('path[stroke]')

    expect(vein).toHaveAttribute('d', 'M12.5 47 27 20 34.5 30 41 24.5 51.5 47')
    expect(vein).toHaveAttribute('stroke', '#FFFFFF')
    expect(vein).toHaveAttribute('fill', 'none')
  })

  it('lightens the ground under the vein', () => {
    const ground = drawnMark().querySelector('path[fill-opacity]')

    expect(ground).toHaveAttribute('d', 'M12.5 47 27 20 34.5 30 41 24.5 51.5 47Z')
    expect(ground).toHaveAttribute('fill', '#FFFFFF')
    expect(ground).toHaveAttribute('fill-opacity', '0.18')
  })

  it('draws nothing but the tile, the ground, the vein and the nugget, in colours no theme changes', () => {
    const mark = drawnMark()

    expect(mark).toHaveAttribute('viewBox', '0 0 64 64')
    expect([...mark.children].map((shape) => shape.tagName)).toEqual(['rect', 'path', 'path', 'circle'])
    expect(mark.querySelectorAll('[class]')).toHaveLength(0)
  })
})
