import { readdirSync, readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import { type Density, gridOf, type Place } from './grid.ts'
import { type Letters, lettersOf } from './lettering.ts'
import type { Model } from './model.ts'
import { pictogramsOf, rowOf } from './pictograms.ts'
import { type Note, type Wall, wallOf } from './wall.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model

const wall = wallOf(BOOKING)

/** Every context's model, by its context. */
const WALLS = 'public/docs/event-storming'
const CONTEXTS = readdirSync(WALLS, { withFileTypes: true })
  .filter((entry) => entry.isDirectory())
  .map((entry) => JSON.parse(readFileSync(`${WALLS}/${entry.name}/model.json`, 'utf8')) as Model)
  .map((model) => [model.context, model] as const)

function noteOf(slug: string, kind: Note['kind']): Note {
  const note = wall.notes.find((candidate) => candidate.slug === slug && candidate.kind === kind)
  if (note === undefined) throw new Error(`No post-it ${slug} on the wall`)
  return note
}

describe('the lettering of a post-it', () => {
  it('writes a label on the lines it wraps to', () => {
    const [label] = lettersOf(noteOf('command:ForgetAppointmentOfErasedExchange', 'command'))

    expect(label?.lines).toEqual(['Forget', 'appointment of', 'erased exchange'])
  })

  it('writes above a policy whenever the event it hears happens', () => {
    const whenever = lettersOf(noteOf('policy:ForgetAppointmentOfErasedExchangePolicy', 'policy')).find(
      (letters) => letters.role === 'whenever',
    )

    expect(whenever?.lines.join(' ')).toBe('whenever exchange facts changed')
  })

  it('stamps an event published to the other contexts', () => {
    const roles = lettersOf(noteOf('published:SlotBooked', 'event')).map((letters) => letters.role)

    expect(roles).toContain('stamp')
  })

  it('wraps the name of a long method at its capitals', () => {
    const long = wallOf(withAMethod('openTheBookingPageForEveryWeek'))
    const aggregate = long.notes.find((note) => note.via === 'openTheBookingPageForEveryWeek')
    const via =
      aggregate === undefined ? undefined : lettersOf(aggregate).find((letters) => letters.role === 'via')

    expect(via?.lines).toEqual(['openThe', 'BookingPage', 'ForEvery', 'Week()'])
  })

  for (const [name, model] of [
    ...CONTEXTS,
    ['a method of 32 letters', withAMethod('openTheBookingPageForEveryWindow')],
  ] as const) {
    for (const density of ['roomy', 'compact'] as const) {
      it(`cuts and covers no label, on ${name} in ${density} rows`, () => {
        expect(defectsOf(wallOf(model), density)).toEqual([])
      })
    }
  }

  for (const [name, model] of CONTEXTS) {
    it(`keeps every pictogram on its post-it, on ${name}`, () => {
      expect(wallOf(model).notes.flatMap(pictogramsOffTheirPostIt)).toEqual([])
    })
  }

  it('lets a hotspot touch no post-it', () => {
    const grid = gridOf(wall, 'roomy')
    const touching = wall.notes
      .filter((note) => note.kind === 'hotspot')
      .flatMap((hotspot) =>
        wall.notes
          .filter(
            (other) =>
              other !== hotspot &&
              crosses(
                boundsOf(hotspot, placeOf(grid.places, hotspot)),
                boundsOf(other, placeOf(grid.places, other)),
              ),
          )
          .map((other) => `${hotspot.label} touches ${other.label}`),
      )

    expect(touching).toEqual([])
  })
})

/**
 * Each pictogram whose centre leaves its post-it, or that covers another pictogram of its post-it or the badge of the
 * member's AI on its corner, the « +n » included. A pictogram of a post-it leans out of its corner, never further.
 */
function pictogramsOffTheirPostIt(note: Note): string[] {
  const { size } = rowOf(note)
  const { shown, more } = pictogramsOf(note)
  const lefts = [
    ...shown.map(({ side, offset }) => (side === 'left' ? offset : note.w - offset - size)),
    ...(more === null ? [] : [note.w - more.offset - size]),
  ].sort((one, other) => one - other)
  const badges = note.step.main === note ? note.step.notes.filter((other) => other.badge) : []
  const off = lefts.filter((left) => left + size / 2 < 0 || left + size / 2 > note.w)
  const covering = lefts.filter((left, rank) => rank > 0 && left < (lefts[rank - 1] ?? left) + size)
  const onABadge = lefts.filter((left) =>
    badges.some((badge) => left < badge.x - note.x + badge.w && badge.x - note.x < left + size),
  )
  return [...off, ...covering, ...onABadge].map((left) => `${note.label}: a pictogram at ${left}`)
}

function withAMethod(method: string): Model {
  return {
    ...BOOKING,
    links: BOOKING.links.map((link) => (link.via === 'open' ? { ...link, via: method } : link)),
  }
}

interface Box {
  left: number
  top: number
  right: number
  bottom: number
}

/** Each label cut by its own paper, or crossed by a post-it painted above its own. */
function defectsOf(wall: Wall, density: Density): string[] {
  const { places } = gridOf(wall, density)
  const shown = wall.notes.filter((note) => !(density === 'compact' && note.port))
  return shown.flatMap((note) =>
    lettersOf(note).flatMap((letters) => {
      const cut = isCut(note, letters) ? [`${note.label}: ${letters.role} cut`] : []
      const box = boundsOf(note, placeOf(places, note), letters)
      const covering = shown
        .filter(
          (other) => isAbove(wall, other, note) && crosses(box, boundsOf(other, placeOf(places, other))),
        )
        .map((other) => `${note.label}: ${letters.role} covered by ${other.label}`)
      return [...cut, ...covering]
    }),
  )
}

function placeOf(places: Map<Note, Place>, note: Note): Place {
  const place = places.get(note)
  if (place === undefined) throw new Error(`${note.label} has no place`)
  return place
}

function isCut(note: Note, letters: Letters): boolean {
  return (
    letters.x < 0 ||
    letters.y < 0 ||
    letters.x + letters.w > note.w + 0.5 ||
    letters.y + letters.h > note.h + 0.5
  )
}

/** The page paints a post-it over another by its z, then by its order on the wall. */
function isAbove(wall: Wall, other: Note, note: Note): boolean {
  return (
    other !== note &&
    (other.z > note.z || (other.z === note.z && wall.notes.indexOf(other) > wall.notes.indexOf(note)))
  )
}

/** The box a post-it, or a text on it, covers on the wall once it leans around the post-it's centre. */
function boundsOf(note: Note, place: Place, letters?: Letters): Box {
  const box = letters === undefined ? { x: 0, y: 0, w: place.w, h: place.h } : letters
  const angle = (note.tilt * Math.PI) / 180
  const centre = { x: place.w / 2, y: place.h / 2 }
  const corners = [
    [box.x, box.y],
    [box.x + box.w, box.y],
    [box.x, box.y + box.h],
    [box.x + box.w, box.y + box.h],
  ].map(([x = 0, y = 0]) => ({
    x: place.x + centre.x + (x - centre.x) * Math.cos(angle) - (y - centre.y) * Math.sin(angle),
    y: place.y + centre.y + (x - centre.x) * Math.sin(angle) + (y - centre.y) * Math.cos(angle),
  }))
  return {
    left: Math.min(...corners.map((corner) => corner.x)),
    top: Math.min(...corners.map((corner) => corner.y)),
    right: Math.max(...corners.map((corner) => corner.x)),
    bottom: Math.max(...corners.map((corner) => corner.y)),
  }
}

function crosses(a: Box, b: Box): boolean {
  return a.left < b.right && b.left < a.right && a.top < b.bottom && b.top < a.bottom
}
