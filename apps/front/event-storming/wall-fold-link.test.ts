import { beforeAll, describe, expect, inject, it, vi } from 'vitest'
import { runTheWall } from './wall-script.ts'

/** Prospecting's wall, the largest, opened from a link that names the lanes it folds. */
const { page: html, data } = inject('prospecting')

function foldedLanes(): string[] {
  return data.lanes
    .filter(
      (_, index) =>
        document.querySelector(`[data-fold="${index}"]`)?.getAttribute('aria-expanded') === 'false',
    )
    .map((lane) => lane.name)
}

beforeAll(() => {
  const page = new DOMParser().parseFromString(html, 'text/html')
  for (const script of page.body.querySelectorAll('script')) script.remove()
  document.body.innerHTML = page.body.innerHTML
  vi.stubGlobal('matchMedia', () => ({ matches: true }))
  history.replaceState(null, '', '#fold=Timeline,Nowhere,Agenda')
  runTheWall(data)
})

describe('a large wall opened from a link that names its folded lanes', () => {
  it('folds the lanes its link names, and those only', () => {
    expect(foldedLanes()).toEqual(['Agenda', 'Timeline'])
  })

  it('keeps in its link the lanes it folds, and forgets a lane it does not hold', () => {
    expect(new URLSearchParams(location.hash.slice(1)).get('fold')).toBe('Timeline,Agenda')
  })
})
