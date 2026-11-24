import { readFileSync } from 'node:fs'
import { beforeAll, describe, expect, it, vi } from 'vitest'
import { gridOf } from './grid.ts'
import type { Model } from './model.ts'
import { pageOf } from './page.ts'
import { dataOf, type WallData } from './page-data.ts'
import { wallOf } from './wall.ts'
import { runTheWall } from './wall-script.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model
const wall = wallOf(BOOKING)
const data = dataOf(wall, { roomy: gridOf(wall, 'roomy'), compact: gridOf(wall, 'compact') })

/** The page's body without its script, which the test runs itself on its data. */
beforeAll(() => {
  const page = new DOMParser().parseFromString(pageOf(BOOKING, { style: '', font: '' }), 'text/html')
  for (const script of page.body.querySelectorAll('script')) script.remove()
  document.body.innerHTML = page.body.innerHTML
  vi.stubGlobal('matchMedia', () => ({ matches: true }))
  runTheWall(data)
})

function stickyOf(slug: string, data: WallData): HTMLElement {
  const index = data.notes.findIndex((note) => note.slug === slug && !note.badge)
  const sticky = document.querySelector<HTMLElement>(`[data-node="${index}"]`)
  if (sticky === null) throw new Error(`No post-it ${slug} on the page`)
  return sticky
}

function press(key: string): void {
  document.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }))
}

describe('the wall in the browser', () => {
  it('pins the story of a post-it, opens its card and names it in the link', () => {
    stickyOf('command:BookSlot', data).click()

    expect({
      pinned: stickyOf('command:BookSlot', data).classList.contains('pinned'),
      lit: stickyOf('PersonalData/policy:IdentifyBookerPolicy', data).classList.contains('lit'),
      card: document.querySelector('#inspector .name')?.textContent,
      link: location.hash,
    }).toEqual({ pinned: true, lit: true, card: 'Book slot', link: '#at=command%3ABookSlot' })
  })

  it('lets the story go on Escape', () => {
    press('Escape')

    expect([
      document.querySelector('.pinned'),
      (document.querySelector('#inspector') as HTMLElement).hidden,
    ]).toEqual([null, true])
  })

  it('says when a dashed line is offered, on the focus of its line', () => {
    const condition = [...document.querySelectorAll<SVGElement>('#hits .hit')].find(
      (hit) => hit.dataset.tip === 'Offered only while the page is closed',
    )
    condition?.dispatchEvent(new FocusEvent('focusin', { bubbles: true }))

    expect(document.querySelector('#tip')?.textContent).toBe('Offered only while the page is closed')
  })

  it('folds a lane into the names of its steps, and the lanes under it move up', () => {
    const publicLink = document.querySelector<HTMLElement>('[data-lane="1"]')
    const before = publicLink?.style.top
    document.querySelector<HTMLElement>('[data-fold="0"]')?.click()

    expect({
      mini: stickyOf('command:OpenBooking', data).classList.contains('mini'),
      names: document.querySelectorAll('#labels .fold-label').length > 0,
      moved: publicLink?.style.top !== before,
      link: location.hash.includes('fold=BookingPage'),
    }).toEqual({ mini: true, names: true, moved: true, link: true })
    document.querySelector<HTMLElement>('[data-fold="0"]')?.click()
  })

  it('turns what a use case asks into squares in compact rows', () => {
    document.querySelector<HTMLElement>('#dense')?.click()
    const port = document.querySelector<HTMLElement>('.sticky.port')

    expect([
      port?.classList.contains('dot'),
      port?.style.width,
      location.hash.includes('rows=compact'),
    ]).toEqual([true, '25px', true])
    document.querySelector<HTMLElement>('#dense')?.click()
  })

  it('finds a post-it by the French title of its feature', () => {
    const search = document.querySelector<HTMLInputElement>('#q') as HTMLInputElement
    search.value = 'créneau'
    search.dispatchEvent(new Event('input'))

    expect(
      [...document.querySelectorAll('#results button')].map(
        (button) => button.firstElementChild?.nextSibling?.firstChild?.textContent,
      ),
    ).toEqual(['Book slot'])
    search.value = ''
    search.dispatchEvent(new Event('input'))
  })

  it('steps a kind of post-it back from its chip in the legend', () => {
    document.querySelector<HTMLElement>('[data-kind="event"]')?.click()

    expect(document.querySelector('#world')?.classList.contains('off-event')).toBe(true)
    document.querySelector<HTMLElement>('[data-kind="event"]')?.click()
  })

  it('reads the wall as text', () => {
    document.querySelector<HTMLElement>('#storyBtn')?.click()

    expect((document.querySelector('#story') as HTMLElement).hidden).toBe(false)
    document.querySelector<HTMLElement>('#storyBtn')?.click()
  })

  it('turns the lights off', () => {
    document.documentElement.dataset.theme = 'light'
    document.querySelector<HTMLElement>('#themeBtn')?.click()

    expect(document.documentElement.dataset.theme).toBe('dark')
  })

  it('jumps from an event to the policy that hears it', () => {
    const booked = data.notes.findIndex((note) => note.slug === 'event:SlotBooked' && note.jumps.length > 0)
    document.querySelector<HTMLElement>(`[data-node="${booked}"] .jump`)?.click()

    expect(location.hash).toBe('#at=policy%3APublishSlotBookedHandler')
  })
})
