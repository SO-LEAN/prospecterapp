import { readdirSync, readFileSync } from 'node:fs'
import { beforeEach, describe, expect, it } from 'vitest'
import { type Density, gridOf, type Place } from './grid.ts'
import { type Letters, lettersOf } from './lettering.ts'
import { arrowOf } from './marker.ts'
import type { Model } from './model.ts'
import { dataOf } from './page-data.ts'
import { pictogramsOf, rowOf } from './pictograms.ts'
import { type Note, type Wall, wallOf } from './wall.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model

/** Built again in each test, so that the mutation knows which tests run each line of the generator. */
let wall: Wall

beforeEach(() => {
  wall = wallOf(BOOKING)
})

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
    for (const density of ['roomy', 'compact'] as const) {
      it(`draws no arrow going left, climbing a row or running under a post-it, on ${name} in ${density} rows`, () => {
        expect(arrowsBackOf(wallOf(model), density)).toEqual([])
      })
    }
  }

  it('runs an arrow down a corridor between the post-its of the rows it crosses, on a wall where they reach far', () => {
    const opened = 'event:Prospecter\\Core\\Booking\\Domain\\Event\\BookingOpened'
    const forget =
      'policy:Prospecter\\Core\\Booking\\Application\\Policy\\ForgetAppointmentOfErasedExchangePolicy'
    const far = wallOf({
      ...BOOKING,
      links: [...BOOKING.links, { from: opened, to: forget, via: null, certain: true, when: null }],
    })

    expect(arrowsBackOf(far, 'roomy')).toEqual([])
  })

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
 * Each pictogram whose centre leaves its post-it, or that covers another of its post-it's pictograms or its member's AI
 * badge, the « +n » included. A pictogram of a post-it leans out of its corner, never further.
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

/**
 * Each arrow of a reaction that goes left, ends in a row above the one it starts from, or runs under a post-it other
 * than its two ends, as the page draws it.
 */
function arrowsBackOf(wall: Wall, density: Density): string[] {
  const grids = { roomy: gridOf(wall, 'roomy'), compact: gridOf(wall, 'compact') }
  const grid = grids[density]
  const data = dataOf(wall, grids)
  const shown = wall.notes.filter((note) => !(density === 'compact' && note.port))
  return data.edges.flatMap((edge) => {
    const from = wall.notes[edge.from]
    const to = wall.notes[edge.to]
    if (from === undefined || to === undefined) return []
    const start = placeOf(grid.places, from)
    const end = placeOf(grid.places, to)
    const named = `${from.label} → ${to.label}`
    const left = end.x < start.x + start.w ? [`${named} goes left`] : []
    const climbs =
      (grid.rows.get(to.step) ?? 0) < (grid.rows.get(from.step) ?? 0) ? [`${named} climbs a row`] : []
    const { curve } = arrowOf(
      { ...start, seed: from.seed },
      { ...end, seed: to.seed },
      edge.certain,
      edge.route[density],
    )
    const points = pointsOf(curve)
    const under = shown
      .filter((note) => note !== from && note !== to && !note.badge)
      .filter((note) => {
        const place = placeOf(grid.places, note)
        return points.some(
          (point) =>
            point.x > place.x + 2 &&
            point.x < place.x + place.w - 2 &&
            point.y > place.y + 2 &&
            point.y < place.y + place.h - 2,
        )
      })
      .map((note) => `${named} runs under ${note.label}`)
    return [...left, ...climbs, ...under]
  })
}

/** Points along a path of moves, lines and curves, a few pixels apart. */
function pointsOf(path: string): { x: number; y: number }[] {
  const points: { x: number; y: number }[] = []
  let at = { x: 0, y: 0 }
  for (const [, command = '', numbers = ''] of path.matchAll(/([MLCQ])([^MLCQ]*)/g)) {
    const values = (numbers.match(/-?[\d.]+/g) ?? []).map(Number)
    const pairs = values.flatMap((value, index) =>
      index % 2 === 0 ? [{ x: value, y: values[index + 1] ?? 0 }] : [],
    )
    const last = pairs.at(-1) ?? at
    if (command !== 'M') {
      const steps = Math.max(40, Math.ceil(Math.hypot(last.x - at.x, last.y - at.y) / 6))
      for (let step = 1; step <= steps; step++) points.push(along(at, pairs, command, step / steps))
    }
    at = last
  }
  return points
}

function along(
  at: { x: number; y: number },
  pairs: { x: number; y: number }[],
  command: string,
  t: number,
): { x: number; y: number } {
  const [a = at, b = at, c = at] = pairs
  const u = 1 - t
  if (command === 'L') return { x: at.x + (a.x - at.x) * t, y: at.y + (a.y - at.y) * t }
  if (command === 'Q')
    return {
      x: u * u * at.x + 2 * u * t * a.x + t * t * b.x,
      y: u * u * at.y + 2 * u * t * a.y + t * t * b.y,
    }
  return {
    x: u * u * u * at.x + 3 * u * u * t * a.x + 3 * u * t * t * b.x + t * t * t * c.x,
    y: u * u * u * at.y + 3 * u * u * t * a.y + 3 * u * t * t * b.y + t * t * t * c.y,
  }
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
