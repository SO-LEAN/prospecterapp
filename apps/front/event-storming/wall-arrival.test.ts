import { readFileSync } from 'node:fs'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import { gridOf } from './grid.ts'
import type { Model } from './model.ts'
import { pageOf } from './page.ts'
import { dataOf } from './page-data.ts'
import { wallOf } from './wall.ts'
import { runTheWall } from './wall-script.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model
const wall = wallOf(BOOKING)
const data = dataOf(wall, { roomy: gridOf(wall, 'roomy'), compact: gridOf(wall, 'compact') })

/** The wall opened from a pictogram of Personal data's wall, on the post-it that answers it. */
beforeAll(() => {
  const page = new DOMParser().parseFromString(pageOf(BOOKING, { style: '', font: '' }), 'text/html')
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
})
