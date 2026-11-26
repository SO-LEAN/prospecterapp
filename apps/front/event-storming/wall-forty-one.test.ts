import { beforeAll, describe, expect, inject, it, vi } from 'vitest'
import { runTheWall } from './wall-script.ts'

/** Booking's model, its use cases counted forty-one: one more than a wall that opens unfolded. */
const { page: html, data } = inject('booking')

beforeAll(() => {
  const counted = data.lanes.reduce((sum, lane) => sum + lane.useCases, 0)
  const [first] = data.lanes
  if (first !== undefined) first.useCases += 41 - counted
  const page = new DOMParser().parseFromString(html, 'text/html')
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
