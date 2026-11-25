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

  it('marks a pictogram that leads nowhere as pointing away', () => {
    const unanswered = new DOMParser().parseFromString(
      pageOf(
        { ...BOOKING, stickies: BOOKING.stickies.map((sticky) => ({ ...sticky, answers: [] })) },
        ASSETS,
      ),
      'text/html',
    )

    expect(unanswered.querySelector('button.jump.away')?.getAttribute('aria-label')).toMatch(/^Answered by /)
  })

  it('lists in a « +n » the pictograms a post-it has no room for, each leading to its wall', () => {
    const more = membersOf()?.querySelector('.more')
    const listed = [...(more?.querySelectorAll('.more-jumps > *') ?? [])]

    expect([
      more?.querySelector('.jump.more')?.textContent,
      listed.map((item) => [item.tagName, item.className, item.getAttribute('href')?.startsWith('../')]),
      more?.querySelectorAll('.more-jumps > * > svg').length,
      more?.querySelector('.more-jumps')?.childNodes.length,
    ]).toEqual([
      '+2',
      [
        ['A', 'away', true],
        ['A', 'away', true],
      ],
      2,
      2,
    ])
  })

  it('names in the « +n » each pictogram it lists, and its own items by what they lead to', () => {
    const more = membersOf()?.querySelector('.more')
    const tips = [...(more?.querySelectorAll('.more-jumps > *') ?? [])].map((item) => item.textContent)

    expect([
      more?.querySelector('.jump.more')?.getAttribute('aria-label'),
      [...(more?.querySelectorAll('.more-jumps > *') ?? [])].map((item) => item.getAttribute('data-jump')),
    ]).toEqual([`2 more: ${tips.join(', ')}`, ['5', '6']])
  })

  it('draws a pictogram of this wall plain, with its arrow, and one away with its own', () => {
    const on = stickyOf('Slot booked', 'button.jump')
    const away = stickyOf('Slot booked', 'a.jump')

    expect([
      on?.className,
      on?.querySelector('path')?.getAttribute('d'),
      away?.querySelector('path')?.getAttribute('d'),
    ]).toEqual(['jump', 'M5 12h13M13 6l6 6-6 6', 'M8 16L18 6M10 6h8v8'])
  })

  it('draws a pictogram back with its arrow back', () => {
    const back = [...document.querySelectorAll('.sticky button.jump')].find((jump) =>
      jump.getAttribute('style')?.startsWith('left:'),
    )

    expect(back?.querySelector('path')?.getAttribute('d')).toBe('M19 12H6M11 6l-6 6 6 6')
  })

  it('writes nothing beside a post-it but its paper and its pictograms', () => {
    const stickies = [...document.querySelectorAll('.sticky'), membersOf()]
    const besides = stickies.flatMap((sticky) =>
      [...(sticky?.childNodes ?? [])].map((node) =>
        node instanceof Element ? (node.className.split(' ')[0] ?? '') : `text ${node.textContent}`,
      ),
    )

    expect([...new Set(besides)].sort()).toEqual(['jump', 'more', 'paper'])
  })

  it('tells the wall as text without its script', () => {
    expect(document.querySelector('#story')?.textContent).toContain('Anyone with the link asks to Book slot')
  })

  it('writes the same page from the same model', () => {
    expect(pageOf(BOOKING, ASSETS)).toBe(page)
  })
})

/** Accounts' published service of the members, whose readers outnumber the room of its post-it. */
function membersOf(): Element | undefined {
  const accounts = JSON.parse(readFileSync('public/docs/event-storming/accounts/model.json', 'utf8')) as Model
  const page = new DOMParser().parseFromString(pageOf(accounts, ASSETS), 'text/html')
  return [...page.querySelectorAll('.sticky')].find(
    (sticky) =>
      sticky.querySelector('.role-label')?.textContent === 'Members' &&
      sticky.querySelector('.more') !== null,
  )
}

/** The post-it of a label, or what it holds of a selector. */
function stickyOf(label: string, inside?: string): HTMLElement | null | undefined {
  const sticky = [...document.querySelectorAll<HTMLElement>('.sticky')].find(
    (candidate) =>
      candidate.querySelector('.role-label')?.textContent === label &&
      (inside === undefined || candidate.querySelector(inside) !== null),
  )
  return inside === undefined ? sticky : sticky?.querySelector<HTMLElement>(inside)
}
