import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import type { Model } from './model.ts'
import { pageOf } from './page.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model
const ASSETS = { style: '.sticky { position: absolute; }', font: 'AAAA' }

const page = pageOf(BOOKING, ASSETS)
const document = new DOMParser().parseFromString(page, 'text/html')

describe('the page of a wall', () => {
  it('writes one wall for the context, each module a lane named on a tape', () => {
    expect([...document.querySelectorAll('.lane-tape')].map((tape) => tape.textContent)).toEqual([
      'Booking page',
      'Public link',
      'Appointment',
    ])
  })

  it('draws each post-it square on its paper, leaning', () => {
    const sticky = stickyOf('Book slot')

    expect([
      sticky?.style.width,
      sticky?.style.height,
      sticky?.querySelector('.paper') !== null,
      sticky?.style.getPropertyValue('--r'),
    ]).toEqual(['132px', '132px', true, expect.stringMatching(/^-?\d+\.\d\ddeg$/)])
  })

  it('writes the labels in the embedded handwriting, and loads nothing from outside', () => {
    const style = document.querySelector('style')?.textContent ?? ''

    expect([
      style.includes('src: url(data:font/woff2;base64,AAAA)'),
      document.querySelectorAll('link, script[src], img[src^="http"]').length,
    ]).toEqual([true, 0])
  })

  it('stamps an event published to the other contexts', () => {
    expect(stickyOf('Slot booked', '.role-stamp')?.textContent).toBe('PUBLISHED')
  })

  it('links a pictogram to the post-it of another wall it jumps to', () => {
    const jump = stickyOf('Slot booked', 'a.jump')

    expect([jump?.getAttribute('href'), jump?.getAttribute('aria-label')]).toEqual([
      '../personal-data/#at=policy%3AIdentifyBookerPolicy',
      'Heard by “Identify booker” · Personal data',
    ])
  })

  it('tells the wall as text without its script', () => {
    expect(document.querySelector('#story')?.textContent).toContain('Anyone with the link asks to Book slot')
  })

  it('writes the same page from the same model', () => {
    expect(pageOf(BOOKING, ASSETS)).toBe(page)
  })
})

/** The post-it of a label, or what it holds of a selector. */
function stickyOf(label: string, inside?: string): HTMLElement | null | undefined {
  const sticky = [...document.querySelectorAll<HTMLElement>('.sticky')].find(
    (candidate) =>
      candidate.querySelector('.role-label')?.textContent === label &&
      (inside === undefined || candidate.querySelector(inside) !== null),
  )
  return inside === undefined ? sticky : sticky?.querySelector<HTMLElement>(inside)
}
