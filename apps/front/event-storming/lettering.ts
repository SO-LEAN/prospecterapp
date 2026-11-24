import { type Font, linesOf, widthOf } from './ink.ts'
import type { Note } from './wall.ts'

/** What a piece of text says on its post-it. */
export type Role = 'label' | 'whenever' | 'via' | 'cap' | 'stamp' | 'answered' | 'bang'

/**
 * A piece of text written on a post-it, placed by the generator: its lines, its box relative to the post-it, its
 * font. The page and the SVGs write it there, and a test checks that no box is cut or covered.
 */
export interface Letters {
  role: Role
  lines: string[]
  x: number
  y: number
  w: number
  h: number
  size: number
  font: Font
  lineHeight: number
  align: 'center' | 'start'
}

interface Padding {
  top: number
  right: number
  bottom: number
  left: number
}

/** The margins of a post-it's paper, as the page's style draws it. */
const PAPER: Padding = { top: 13, right: 9, bottom: 15, left: 9 }
const AGGREGATE: Padding = { top: 26, right: 27, bottom: 0, left: 25 }
const PORT: Padding = { top: 13, right: 6, bottom: 3, left: 10 }
const HOTSPOT: Padding = { top: 8, right: 8, bottom: 8, left: 8 }
const ACTOR: Padding = { top: 5, right: 3, bottom: 4, left: 3 }
const FIGURE = 30
const GAP = 2
const LINE = { label: 1.08, small: 1.05, ui: 1.1, mono: 1.15, bang: 0.9, actor: 0.98 }
const SIZES = {
  whenever: 12.5,
  cap: 9,
  stamp: 8,
  via: 10.5,
  aggregate: 18,
  port: 16,
  answered: 9.5,
  hotspot: 15,
  bang: 22,
  actor: 13.5,
}
/** The stamp's frame around its letters, and its place in the corner. */
const STAMP = { padding: 6, line: 1.3, right: 5, top: 7, letterSpacing: 0.12 }
const CAP_BOTTOM = 5

/** A long label is written smaller, so that it holds on the post-it. */
export function fontSizeOf(note: Note): number {
  const length = note.label.length + (note.whenever !== null ? 8 : 0)
  return length <= 13 ? 21 : length <= 22 ? 18.5 : length <= 32 ? 16.5 : 15
}

export function lettersOf(note: Note): Letters[] {
  if (note.badge) return []
  if (note.kind === 'actor') return actorLetters(note)
  if (note.port) return portLetters(note)
  if (note.kind === 'hotspot')
    return centred(note, HOTSPOT, [
      block('bang', '?!', SIZES.bang, LINE.bang, note, HOTSPOT),
      block('label', note.label, SIZES.hotspot, LINE.label, note, HOTSPOT),
    ])
  if (note.kind === 'aggregate') return aggregateLetters(note)
  return paperLetters(note)
}

function paperLetters(note: Note): Letters[] {
  const stack = [
    ...(note.whenever !== null
      ? [
          block(
            'whenever',
            `whenever ${note.whenever.toLowerCase()}`,
            SIZES.whenever,
            LINE.small,
            note,
            PAPER,
          ),
        ]
      : []),
    block('label', note.label, fontSizeOf(note), LINE.label, note, PAPER),
  ]
  return [
    ...centred(note, PAPER, stack),
    ...(note.kind === 'policy' ? [cap(note)] : []),
    ...(note.published ? [stamp(note)] : []),
  ]
}

function aggregateLetters(note: Note): Letters[] {
  const label = block('label', note.label, SIZES.aggregate, LINE.label, note, AGGREGATE)
  const via = block('via', `${note.via ?? ''}()`, SIZES.via, LINE.mono, note, AGGREGATE, 'mono')
  const top = AGGREGATE.top
  return [
    { ...label, y: top },
    { ...via, y: top + label.h + 3 },
  ]
}

function actorLetters(note: Note): Letters[] {
  const label = block('label', note.label, SIZES.actor, LINE.actor, note, ACTOR)
  return [{ ...label, y: ACTOR.top + FIGURE }]
}

function portLetters(note: Note): Letters[] {
  const width = note.w - PORT.left - PORT.right
  const line = (role: Role, text: string, size: number, font: Font, y: number): Letters => ({
    role,
    lines: [text],
    x: PORT.left,
    y,
    w: Math.min(width, widthOf(text, size, font)),
    h: size * LINE.ui,
    size,
    font,
    lineHeight: LINE.ui,
    align: 'start',
  })
  const label = line('label', note.label, SIZES.port, 'hand', PORT.top)
  const answeredBy = note.sticky?.answeredBy ?? []
  if (answeredBy.length === 0) return [label]
  return [label, line('answered', `asks ${answeredBy.join(' · ')}`, SIZES.answered, 'ui', PORT.top + label.h)]
}

/** A block of text, centred in the width of its post-it, its lines wrapped to it. */
function block(
  role: Role,
  text: string,
  size: number,
  lineHeight: number,
  note: Note,
  padding: Padding,
  font: Font = 'hand',
): Letters {
  const room = note.w - padding.left - padding.right
  const lines = linesOf(text, size, font, room)
  const w = Math.max(...lines.map((line) => widthOf(line, size, font)))
  return {
    role,
    lines,
    x: (note.w - w) / 2,
    y: 0,
    w,
    h: lines.length * size * lineHeight,
    size,
    font,
    lineHeight,
    align: 'center',
  }
}

/** The blocks stacked one under the other, centred in the height of the post-it's paper. */
function centred(note: Note, padding: Padding, blocks: Letters[]): Letters[] {
  const height = blocks.reduce((total, letters) => total + letters.h, 0) + GAP * (blocks.length - 1)
  let y = padding.top + (note.h - padding.top - padding.bottom - height) / 2
  return blocks.map((letters) => {
    const placed = { ...letters, y }
    y += letters.h + GAP
    return placed
  })
}

function cap(note: Note): Letters {
  const text = 'POLICY'
  const w = widthOf(text, SIZES.cap, 'ui') * (1 + 0.08)
  return {
    role: 'cap',
    lines: [text],
    x: (note.w - w) / 2,
    y: note.h - CAP_BOTTOM - SIZES.cap,
    w,
    h: SIZES.cap,
    size: SIZES.cap,
    font: 'ui',
    lineHeight: 1,
    align: 'center',
  }
}

function stamp(note: Note): Letters {
  const text = 'PUBLISHED'
  const w = widthOf(text, SIZES.stamp, 'ui') * (1 + STAMP.letterSpacing) + 2 * STAMP.padding
  const h = SIZES.stamp * STAMP.line + 4
  return {
    role: 'stamp',
    lines: [text],
    x: note.w - STAMP.right - w,
    y: STAMP.top,
    w,
    h,
    size: SIZES.stamp,
    font: 'ui',
    lineHeight: STAMP.line,
    align: 'center',
  }
}
