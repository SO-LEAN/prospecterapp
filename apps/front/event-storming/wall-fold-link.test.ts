import { readFileSync } from 'node:fs'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import { gridOf } from './grid.ts'
import type { Model } from './model.ts'
import { pageOf } from './page.ts'
import { dataOf } from './page-data.ts'
import { wallOf } from './wall.ts'
import { runTheWall } from './wall-script.ts'

/** Prospecting's wall, the largest, opened from a link that names the lanes it folds. */
const PROSPECTING = JSON.parse(
  readFileSync('public/docs/event-storming/prospecting/model.json', 'utf8'),
) as Model
const wall = wallOf(PROSPECTING)
const data = dataOf(wall, { roomy: gridOf(wall, 'roomy'), compact: gridOf(wall, 'compact') })

function foldedLanes(): string[] {
  return data.lanes
    .filter(
      (_, index) =>
        document.querySelector(`[data-fold="${index}"]`)?.getAttribute('aria-expanded') === 'false',
    )
    .map((lane) => lane.name)
}

beforeAll(() => {
  const page = new DOMParser().parseFromString(pageOf(PROSPECTING, { style: '', font: '' }), 'text/html')
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
