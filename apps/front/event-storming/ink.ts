import { HAND_ADVANCES } from './glyphs.ts'

/** The handwriting of the post-its, the system's font of the small print, the monospace of a method. */
export type Font = 'hand' | 'ui' | 'mono'

/** The average advance of a letter, in ems, of the fonts whose file the page does not hold. */
const AVERAGE_ADVANCE: Record<Exclude<Font, 'hand'>, number> = { ui: 0.56, mono: 0.62 }
const UNKNOWN_LETTER = 0.5

export function widthOf(text: string, size: number, font: Font): number {
  if (font !== 'hand') return text.length * AVERAGE_ADVANCE[font] * size
  return [...text].reduce((width, letter) => width + (HAND_ADVANCES[letter] ?? UNKNOWN_LETTER), 0) * size
}

/**
 * The lines a text wraps to within a width, at its spaces. A word too long for a line wraps at its capitals, as a
 * method's name does, then where the line ends.
 */
export function linesOf(text: string, size: number, font: Font, width: number): string[] {
  const fits = (line: string): boolean => widthOf(line, size, font) <= width
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
