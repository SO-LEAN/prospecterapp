import { beforeAll, describe, expect, inject, it, vi } from 'vitest'
import { runTheWall } from './wall-script.ts'

/** Booking's wall and its data, built before the tests (test/scripted-walls.ts). */
const { page: html, data } = inject('booking')

/** The wall opened from a pictogram of Personal data's wall, on the post-it that answers it. */
beforeAll(() => {
  const page = new DOMParser().parseFromString(html, 'text/html')
  for (const script of page.body.querySelectorAll('script')) script.remove()
  document.body.innerHTML = page.body.innerHTML
  vi.stubGlobal('matchMedia', () => ({ matches: true }))
  vi.spyOn(document, 'referrer', 'get').mockReturnValue(
    'https://prospecter.localhost/docs/event-storming/personal-data/',
  )
  history.replaceState(null, '', `#at=${encodeURIComponent('readModel:ConsultAppointments')}`)
  runTheWall(data)
})

describe('a wall opened from another wall', () => {
  it('opens on the post-it its link names', () => {
    const index = data.notes.findIndex((note) => note.slug === 'readModel:ConsultAppointments')

    expect(document.querySelector(`[data-node="${index}"]`)?.classList.contains('pinned')).toBe(true)
  })

  it('lets Back return to the wall it came from', () => {
    expect(document.querySelector<HTMLButtonElement>('#back')?.disabled).toBe(false)
  })

  it('keeps Back to the wall it came from once back where it opened', async () => {
    document.querySelector<HTMLElement>('.sticky.k-command')?.click()
    const back = new Promise((resolve) => addEventListener('popstate', resolve, { once: true }))
    history.back()
    await back

    expect(document.querySelector<HTMLButtonElement>('#back')?.disabled).toBe(false)
  })
})
