import { readFileSync } from 'node:fs'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import { gridOf } from './grid.ts'
import type { Model } from './model.ts'
import { pageOf } from './page.ts'
import { dataOf } from './page-data.ts'
import { wallOf } from './wall.ts'
import { runTheWall } from './wall-script.ts'

/** Prospecting's model, the largest wall, as the generator last read it from the code. */
const PROSPECTING = JSON.parse(
  readFileSync('public/docs/event-storming/prospecting/model.json', 'utf8'),
) as Model
const wall = wallOf(PROSPECTING)
const data = dataOf(wall, { roomy: gridOf(wall, 'roomy'), compact: gridOf(wall, 'compact') })

function isFolded(lane: string): boolean {
  const index = data.lanes.findIndex((candidate) => candidate.name === lane)
  return document.querySelector(`[data-fold="${index}"]`)?.getAttribute('aria-expanded') === 'false'
}

/** The wall opened on a post-it of its Agenda lane, from a link of another wall. */
beforeAll(() => {
  const page = new DOMParser().parseFromString(pageOf(PROSPECTING, { style: '', font: '' }), 'text/html')
  for (const script of page.body.querySelectorAll('script')) script.remove()
  document.body.innerHTML = page.body.innerHTML
  vi.stubGlobal('matchMedia', () => ({ matches: true }))
  history.replaceState(null, '', `#at=${encodeURIComponent('readModel:ConsultHeldExchanges')}`)
  runTheWall(data)
})

describe('a large wall', () => {
  it('opens its lanes folded, but the lane of the post-it its link names', () => {
    expect([isFolded('Organization'), isFolded('Contact'), isFolded('Agenda')]).toEqual([true, true, false])
  })

  it('unfolds the lane of a post-it it is asked to show', () => {
    const index = data.notes.findIndex((note) => note.slug === 'command:StartProspecting')
    document.querySelector<HTMLElement>(`[data-node="${index}"]`)?.click()

    expect(isFolded('Organization')).toBe(false)
  })

  it('keeps in its link the lanes left folded', () => {
    expect(new URLSearchParams(location.hash.slice(1)).get('fold')?.split(',')).not.toContain('Agenda')
  })
})
