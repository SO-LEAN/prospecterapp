import { readFileSync } from 'node:fs'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import { gridOf } from './grid.ts'
import type { Model } from './model.ts'
import { pageOf } from './page.ts'
import { dataOf } from './page-data.ts'
import { wallOf } from './wall.ts'
import { runTheWall } from './wall-script.ts'

/** Booking's model, its use cases counted forty-one: one more than a wall that opens unfolded. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model
const wall = wallOf(BOOKING)
const data = dataOf(wall, { roomy: gridOf(wall, 'roomy'), compact: gridOf(wall, 'compact') })

beforeAll(() => {
  const counted = data.lanes.reduce((sum, lane) => sum + lane.useCases, 0)
  const [first] = data.lanes
  if (first !== undefined) first.useCases += 41 - counted
  const page = new DOMParser().parseFromString(pageOf(BOOKING, { style: '', font: '' }), 'text/html')
  for (const script of page.body.querySelectorAll('script')) script.remove()
  document.body.innerHTML = page.body.innerHTML
  vi.stubGlobal('matchMedia', () => ({ matches: true }))
  history.replaceState(null, '', '#')
  runTheWall(data)
})

describe('a wall of forty-one use cases, opened on no post-it', () => {
  it('opens with every lane folded', () => {
    expect(document.querySelectorAll('.lane-fold[aria-expanded="false"]').length).toBe(data.lanes.length)
  })
})
