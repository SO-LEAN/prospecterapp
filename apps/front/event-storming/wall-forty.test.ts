import { beforeAll, describe, expect, inject, it, vi } from 'vitest'
import { runTheWall } from './wall-script.ts'

/** Booking's model, its use cases counted forty: as many as a wall that still opens unfolded. */
const { page: html, data } = inject('booking')

beforeAll(() => {
  const counted = data.lanes.reduce((sum, lane) => sum + lane.useCases, 0)
  const [first] = data.lanes
  if (first !== undefined) first.useCases += 40 - counted
  const page = new DOMParser().parseFromString(html, 'text/html')
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

  it('turns Back on again once gone forward', async () => {
    const forward = new Promise((resolve) => addEventListener('popstate', resolve, { once: true }))
    history.forward()
    await forward

    expect(document.querySelector<HTMLButtonElement>('#back')?.disabled).toBe(false)
  })
})
