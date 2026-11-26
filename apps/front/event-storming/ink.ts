import { HAND_ADVANCES } from './glyphs.ts'

/** The handwriting of the post-its, the system's font of the small print, the monospace of a method. */
export type Font = 'hand' | 'ui' | 'mono'

/** The average advance of a letter, in ems, of the fonts whose file the page does not hold. */
const AVERAGE_ADVANCE: Record<Exclude<Font, 'hand'>, number> = { ui: 0.56, mono: 0.62 }
const UNKNOWN_LETTER = 0.5

/**
 * A browser draws the handwriting at a small size with each letter on whole pixels, wider than the font's advances
 * add up to: "Publish slot booked" measures 113 px from them at 16.5 px, and Chromium draws it on 120. So each letter
 * counts its advance rounded up to the next pixel.
 */
export function widthOf(text: string, size: number, font: Font): number {
  if (font !== 'hand') return text.length * AVERAGE_ADVANCE[font] * size
  return [...text].reduce(
    (width, letter) => width + Math.ceil((HAND_ADVANCES[letter] ?? UNKNOWN_LETTER) * size),
    0,
  )
}

/**
 * The lines a text wraps to within a width, at its spaces. A word too long for a line wraps at its capitals, as a
 * method's name does, then where the line ends.
 */
export function linesOf(text: string, size: number, font: Font, width: number): string[] {
  const fits = (line: string): boolean => width >= widthOf(line, size, font)
  const lines: string[] = []
  for (const { piece, glued } of text.split(/\s+/).flatMap((word) => piecesOf(word, fits))) {
    const last = lines.at(-1)
    const joined = `${last}${glued ? '' : ' '}${piece}`
    if (last !== undefined && fits(joined)) lines[lines.length - 1] = joined
    else lines.push(piece)
  }
  return lines
}

interface Piece {
  piece: string
  /** Written right after the piece before, without a space. */
  glued: boolean
}

function piecesOf(word: string, fits: (line: string) => boolean): Piece[] {
  if (fits(word)) return [{ piece: word, glued: false }]
  return word
    .split(/(?<=[a-z])(?=[A-Z])/)
    .flatMap((part) => lettersOf(part, fits))
    .map((piece, index) => ({ piece, glued: index > 0 }))
}

function lettersOf(part: string, fits: (line: string) => boolean): string[] {
  const cuts: string[] = []
  for (const letter of part) {
    const last = cuts.at(-1)
    if (last !== undefined && fits(last + letter)) cuts[cuts.length - 1] = last + letter
    else cuts.push(letter)
  }
  return cuts
}
