import { readFileSync } from 'node:fs'
import { beforeEach, describe, expect, it } from 'vitest'
import type { Model } from './model.ts'
import { cardOf, sentenceAbout, storyOf } from './story.ts'
import { type Note, type Wall, wallOf } from './wall.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model

/** Built again in each test, so that the mutation knows which tests run each line of the generator. */
let wall: Wall

beforeEach(() => {
  wall = wallOf(BOOKING)
})

function noteOf(slug: string, kind: Note['kind']): Note {
  const note = wall.notes.find(
    (candidate) => candidate.slug === slug && candidate.kind === kind && !candidate.badge,
  )
  if (note === undefined) throw new Error(`No post-it ${slug} on the wall`)
  return note
}

describe('the story of a post-it', () => {
  it('lights from who asks to the other context that hears what happened', () => {
    const story = [...storyOf(wall, noteOf('command:BookSlot', 'command'))].map((note) => note.label)

    expect(story).toEqual(
      expect.arrayContaining([
        'Anyone with the link',
        'Book slot',
        'Booking page',
        'Slot booked',
        'Publish slot booked',
        'Identify booker',
      ]),
    )
  })

  it('names on the card of a reaction moved into the lane of its event its own module, and the lane it stands in', () => {
    const prospecting = wallOf(
      JSON.parse(readFileSync('public/docs/event-storming/prospecting/model.json', 'utf8')) as Model,
    )
    const pushBack = prospecting.notes.find(
      (note) => note.kind === 'policy' && note.label === 'Push back erasure of contact to attach',
    )

    expect(pushBack === undefined ? '' : cardOf(prospecting, pushBack)).toContain(
      '<dt>Where</dt><dd>Prospecting › Contact, in the lane of Exchange, whose event it hears</dd>',
    )
  })

  it('lights the view that offers a command, and the actor who reads it', () => {
    const story = [...storyOf(wall, noteOf('command:OpenBooking', 'command'))].map((note) => note.label)

    expect(story).toEqual(expect.arrayContaining(['Member', 'My booking page', 'Booking opened']))
  })

  it('leaves out the other commands a view offers', () => {
    const story = [...storyOf(wall, noteOf('command:OpenBooking', 'command'))].map((note) => note.label)

    expect(story).not.toContain('Close booking')
  })

  it("gives a post-it's card its class, its module, its feature in French, and what comes before and after", () => {
    const card = cardIn(cardOf(wall, noteOf('command:BookSlot', 'command')))

    expect({
      class: card.querySelector('code')?.textContent,
      where: termOf(card, 'Where'),
      feature: card.querySelector('[lang="fr"]')?.textContent,
      before: termOf(card, 'Before'),
      after: termOf(card, 'Then'),
      copy: card.querySelector('[data-copy]')?.textContent,
    }).toEqual({
      class: 'Prospecter\\Core\\Booking\\Application\\PublicLink\\BookSlot\\BookSlot',
      where: 'Booking › Public link',
      feature: '« Réserver un créneau »',
      before: 'Anyone with the link',
      after: 'Connected calendarsHost agendasHostsBooking pageOffered where?',
      copy: 'Copy the link to this post-it',
    })
  })

  it('names in a card the view that offers a command, its link and its condition', () => {
    const card = cardIn(cardOf(wall, noteOf('command:OpenBooking', 'command')))

    expect(termOf(card, 'Offered by')).toBe('My booking page link open, only while the page is closed')
  })

  it('tells a step in one sentence', () => {
    const step = noteOf('command:BookSlot', 'command').step

    expect(cardIn(sentenceAbout(wall, step)).textContent).toBe(
      'Anyone with the link asks to Book slot, which asks Connected calendars, Host agendas, Hosts. Booking page records Slot booked. ?! Offered where?',
    )
  })
})

function cardIn(html: string): HTMLElement {
  const card = document.createElement('div')
  card.innerHTML = html
  return card
}

function termOf(card: HTMLElement, term: string): string | undefined {
  const terms = [...card.querySelectorAll('dt')]
  return terms.find((dt) => dt.textContent === term)?.nextElementSibling?.textContent ?? undefined
}
