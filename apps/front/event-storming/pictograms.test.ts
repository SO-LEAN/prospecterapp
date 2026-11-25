import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import type { Model } from './model.ts'
import { pictogramsOf } from './pictograms.ts'
import { type Jump, type Note, wallOf } from './wall.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model

const command = noteOf('command:OpenBooking')

function noteOf(slug: string): Note {
  const note = wallOf(BOOKING).notes.find((candidate) => candidate.slug === slug)
  if (note === undefined) throw new Error(`No post-it ${slug} on the wall`)
  return note
}

function jumps(count: number, back = false): Jump[] {
  return Array.from({ length: count }, (_, rank) => ({
    to: null,
    href: `#${rank}`,
    back,
    tip: `Jump ${rank}`,
  }))
}

describe('the pictograms of a post-it', () => {
  it('stands the pictograms back from its left edge, the others from its right edge', () => {
    const pictograms = pictogramsOf({ ...command, jumps: [...jumps(2), ...jumps(1, true)] })

    expect(pictograms.shown.map(({ index, side, offset }) => [index, side, offset])).toEqual([
      [0, 'right', -9],
      [1, 'right', 16],
      [2, 'left', -9],
    ])
  })

  it('shows the pictograms its width holds, then a « +n » that lists the others', () => {
    const pictograms = pictogramsOf({ ...command, jumps: jumps(8) })

    expect([
      pictograms.shown.map(({ index }) => index),
      pictograms.more?.offset,
      pictograms.more?.hidden.map(({ index }) => index),
    ]).toEqual([[0, 1, 2, 3, 4], 116, [5, 6, 7]])
  })

  it('leaves the pictograms back their room on the left', () => {
    const pictograms = pictogramsOf({ ...command, jumps: [...jumps(2, true), ...jumps(6)] })

    expect([
      pictograms.shown.map(({ index }) => index),
      pictograms.more?.offset,
      pictograms.more?.hidden.map(({ index }) => index),
    ]).toEqual([[2, 3, 4, 0, 1], 66, [5, 6, 7]])
  })

  it('stands the smaller pictograms of a port inside its strip', () => {
    const port = wallOf(BOOKING).notes.find((note) => note.port)
    if (port === undefined) throw new Error('No port on the wall')
    const pictograms = pictogramsOf({ ...port, jumps: jumps(9) })

    expect([pictograms.shown.map(({ offset }) => offset), pictograms.more?.offset]).toEqual([
      [4, 25, 46, 67, 88, 109],
      130,
    ])
  })
})
