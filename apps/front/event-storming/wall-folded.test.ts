import { beforeAll, describe, expect, inject, it, vi } from 'vitest'
import { runTheWall } from './wall-script.ts'

/** Prospecting's wall, the largest, and its data, built before the tests (test/scripted-walls.ts). */
const { page: html, data } = inject('prospecting')

function isFolded(lane: string): boolean {
  const index = data.lanes.findIndex((candidate) => candidate.name === lane)
  return document.querySelector(`[data-fold="${index}"]`)?.getAttribute('aria-expanded') === 'false'
}

/** The wall opened on a post-it of its Agenda lane, from a link of another wall. */
beforeAll(() => {
  const page = new DOMParser().parseFromString(html, 'text/html')
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
