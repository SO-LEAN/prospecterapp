import type { Jump, Note } from './wall.ts'

/** A pictogram where it stands on its post-it: its jump, its rank among the post-it's jumps, its edge and offset. */
export interface Pictogram {
  jump: Jump
  index: number
  side: 'left' | 'right'
  offset: number
}

/** The pictograms a post-it shows, and the « +n » that lists those it has no room for. */
export interface Pictograms {
  shown: Pictogram[]
  more: { offset: number; hidden: Pictogram[] } | null
}

/**
 * A pictogram's size, its offset from its edge, and the pitch of each side. A post-it's pictograms lean out of its
 * corners; a port's smaller ones stand inside its strip.
 */
export const ROWS = {
  sticky: { size: 23, edge: -9, pitch: { left: 26, right: 25 } },
  port: { size: 19, edge: 4, pitch: { left: 21, right: 21 } },
} as const

type Row = (typeof ROWS)[keyof typeof ROWS]

export function rowOf(note: Note): Row {
  return note.port ? ROWS.port : ROWS.sticky
}

/**
 * A jump back stands from the post-it's left edge, the others from its right edge, as far as the post-it's width and
 * the jumps back leave room. Past that room, the last place holds a « +n » that lists the others.
 */
export function pictogramsOf(note: Note): Pictograms {
  const row = rowOf(note)
  const ranked = note.jumps.map((jump, index) => ({ jump, index }))
  const onItsSide = (side: Pictogram['side']): Pictogram[] =>
    ranked
      .filter(({ jump }) => jump.back === (side === 'left'))
      .map((ranked, rank) => ({ ...ranked, side, offset: offsetOf(row, side, rank) }))
  const left = onItsSide('left')
  const right = onItsSide('right')
  const room = roomOnTheRight(note, row, left.length)
  if (right.length <= room) return { shown: [...right, ...left], more: null }
  const kept = room - 1
  return {
    shown: [...right.slice(0, kept), ...left],
    more: { offset: offsetOf(row, 'right', kept), hidden: right.slice(kept) },
  }
}

function offsetOf(row: Row, side: Pictogram['side'], rank: number): number {
  return row.edge + rank * row.pitch[side]
}

/** How many pictograms stand from the right edge before they reach the left edge, or the last jump back. */
function roomOnTheRight(note: Note, row: Row, back: number): number {
  const limit = back === 0 ? row.edge : offsetOf(row, 'left', back - 1) + row.size
  return Math.floor((note.w - row.edge - row.size - limit) / row.pitch.right) + 1
}
