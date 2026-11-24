import { readFileSync } from 'node:fs'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import { gridOf } from './grid.ts'
import type { Model } from './model.ts'
import { pageOf } from './page.ts'
import { dataOf } from './page-data.ts'
import { wallOf } from './wall.ts'
import { runTheWall } from './wall-script.ts'

/** Booking's model, its use cases counted forty: as many as a wall that still opens unfolded. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model
const wall = wallOf(BOOKING)
const data = dataOf(wall, { roomy: gridOf(wall, 'roomy'), compact: gridOf(wall, 'compact') })

beforeAll(() => {
  const counted = data.lanes.reduce((sum, lane) => sum + lane.useCases, 0)
  const [first] = data.lanes
  if (first !== undefined) first.useCases += 40 - counted
  const page = new DOMParser().parseFromString(pageOf(BOOKING, { style: '', font: '' }), 'text/html')
  for (const script of page.body.querySelectorAll('script')) script.remove()
  document.body.innerHTML = page.body.innerHTML
  vi.stubGlobal('matchMedia', () => ({ matches: true }))
  history.replaceState(null, '', '#')
  runTheWall(data)
})

describe('a wall of forty use cases', () => {
  it('opens with every lane unfolded', () => {
    expect(document.querySelectorAll('.lane-fold[aria-expanded="false"]').length).toBe(0)
  })

  it('turns Back off once back where it opened', async () => {
    document.querySelector<HTMLElement>('.sticky.k-command')?.click()
    const back = new Promise((resolve) => addEventListener('popstate', resolve, { once: true }))
    history.back()
    await back

    expect(document.querySelector<HTMLButtonElement>('#back')?.disabled).toBe(true)
  })
})
