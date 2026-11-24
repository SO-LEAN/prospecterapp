import { type Grid, gridOf, type Place } from './grid.ts'
import { escaped } from './html.ts'
import { type Letters, lettersOf } from './lettering.ts'
import { arrowOf, braceOf, type Placed, wobbly } from './marker.ts'
import type { Model } from './model.ts'
import { FIGURES } from './page.ts'
import { dataOf, type WallData } from './page-data.ts'
import { type Kind, type Note, sentenceOf, wallOf } from './wall.ts'

/** The colours of the wall in the light, as the page draws them. */
const COLOURS: Record<Kind, string> = {
  actor: '#ffe257',
  command: '#7cc4f8',
  aggregate: '#fff4ad',
  event: '#ffa348',
  policy: '#cfb3f3',
  readModel: '#a9e190',
  externalSystem: '#ffb5d0',
  hotspot: '#ff4d9d',
}
const INK = {
  paper: '#fbf9f3',
  marker: '#252a3a',
  text: '#1b2030',
  soft: '#5e6270',
  tape: '#f0e2a8',
  kraft: '#cfae7e',
  kraftInk: '#4a3516',
  stamp: '#9c1f1f',
}
const FONTS = {
  hand: '"Wall Hand", "Patrick Hand", cursive',
  ui: 'system-ui, sans-serif',
  mono: 'ui-monospace, monospace',
}
/** The share of a line's height above its baseline. */
const ASCENT = 0.8

/**
 * The wall drawn for the repository's documents: one SVG of the whole wall, and one per lane, named after its
 * module. GitHub shows them as images, so they hold their handwriting and need no script.
 */
export function svgsOf(model: Model, font: string): Record<string, string> {
  const wall = wallOf(model)
  const grid = gridOf(wall, 'roomy')
  const data = dataOf(wall, { roomy: grid, compact: gridOf(wall, 'compact') })
  const drawing = new Drawing(wall.notes, grid, data, font)
  const svgs: Record<string, string> = { 'wall.svg': drawing.of(null) }
  data.lanes.forEach((lane, index) => {
    svgs[`${kebabOf(lane.name)}.svg`] = drawing.of(index)
  })
  return svgs
}

function kebabOf(name: string): string {
  return sentenceOf(name).toLowerCase().replaceAll(' ', '-')
}

class Drawing {
  private readonly notes: Note[]
  private readonly grid: Grid
  private readonly data: WallData
  private readonly font: string

  constructor(notes: Note[], grid: Grid, data: WallData, font: string) {
    this.notes = notes
    this.grid = grid
    this.data = data
    this.font = font
  }

  /** The whole wall, or one lane of it. */
  of(lane: number | null): string {
    const shown = (at: number): boolean => lane === null || this.data.notes[at]?.lane === lane
    const top = lane === null ? 0 : (this.data.lanes[lane]?.top.roomy ?? 0)
    const height = lane === null ? this.grid.height : (this.data.lanes[lane]?.height.roomy ?? 0)
    const width = this.grid.width
    const title =
      lane === null
        ? `${this.data.label}, event storming`
        : `${this.data.label} › ${this.data.lanes[lane]?.label ?? ''}`
    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 ${top} ${width} ${height}" width="${width}" height="${height}" role="img" aria-label="${escaped(title)}">
<title>${escaped(title)}</title>
<style>@font-face { font-family: "Wall Hand"; src: url(data:font/woff2;base64,${this.font}) format("woff2"); } .marker { fill: none; stroke: ${INK.marker}; stroke-linecap: round; stroke-linejoin: round; }</style>
<rect x="0" y="${top}" width="${width}" height="${height}" fill="${INK.paper}"/>
${lane === null ? this.headOf(width) : ''}
${this.rulesOf(lane, width)}
${this.data.strips
  .filter((strip) => shown(strip.note))
  .map((strip) => this.stripOf(strip.places.roomy, strip.context, strip.tilt))
  .join('\n')}
${this.inkOf(shown, lane)}
${this.notes.map((note, at) => (shown(at) ? this.stickyOf(note) : '')).join('\n')}
${this.data.lanes.map((each, index) => (lane === null || lane === index ? this.tapeOf(each.label, each.top.roomy) : '')).join('\n')}
</svg>
`
  }

  private headOf(width: number): string {
    return `<text x="40" y="70" font-family='${FONTS.hand}' font-size="58" fill="${INK.marker}">${escaped(this.data.label)}</text>
<path class="marker" stroke-width="3" opacity="0.5" d="${wobbly({ x: 700, y: 62 }, { x: width - 70, y: 62 }, 'time')} M${width - 70},62 l-17,-10 M${width - 70},62 l-17,10"/>
<text x="640" y="69" font-family='${FONTS.hand}' font-size="22" fill="${INK.soft}">time</text>`
  }

  private rulesOf(lane: number | null, width: number): string {
    return this.data.lanes
      .slice(1)
      .map((each, index) =>
        lane === null
          ? `<path class="marker" stroke-width="2.2" opacity="0.2" d="${wobbly({ x: 26, y: each.top.roomy }, { x: width - 26, y: each.top.roomy }, this.data.lanes[index + 1]?.name ?? '')}"/>`
          : '',
      )
      .join('')
  }

  private tapeOf(label: string, top: number): string {
    const width = label.length * 15 + 44
    return `<g transform="translate(30 ${top + 22}) rotate(-2.2)"><rect width="${width}" height="44" fill="${INK.tape}"/><text x="22" y="32" font-family='${FONTS.hand}' font-size="31" fill="#2a2512">${escaped(label)}</text></g>`
  }

  private stripOf(at: Place, context: string, tilt: number): string {
    return `<g transform="translate(${at.x} ${at.y}) rotate(${tilt.toFixed(2)} ${at.w / 2} ${at.h / 2})"><rect width="${at.w}" height="${at.h}" fill="${INK.kraft}"/><text x="10" y="16" font-family='${FONTS.ui}' font-size="10.5" font-weight="600" letter-spacing="1" fill="${INK.kraftInk}">${escaped(context.toUpperCase())}</text></g>`
  }

  private placed(at: number): Placed {
    const note = this.data.notes[at]
    return { ...(note?.places.roomy ?? { x: 0, y: 0, w: 0, h: 0 }), seed: note?.seed ?? '' }
  }

  private inkOf(shown: (at: number) => boolean, lane: number | null): string {
    const arrows = this.data.edges
      .filter((edge) => shown(edge.from) && shown(edge.to))
      .map((edge) => {
        const arrow = arrowOf(this.placed(edge.from), this.placed(edge.to), edge.certain)
        const maybe =
          arrow.maybe === null
            ? ''
            : `<text x="${arrow.maybe.x}" y="${arrow.maybe.y}" text-anchor="middle" font-family='${FONTS.hand}' font-size="17" fill="${INK.marker}">maybe</text>`
        return `<path class="marker" stroke-width="3.4"${edge.certain ? '' : ' stroke-dasharray="4 9"'} d="${arrow.curve}"/><path class="marker" stroke-width="3.4" d="${arrow.head}"/>${maybe}`
      })
    const braces = this.data.braces
      .filter((brace) => lane === null || brace.lane === lane)
      .map((brace) => {
        const drawn = braceOf(
          this.placed(brace.from),
          brace.ends.map((end) => this.placed(end)),
          brace.spine,
          brace.over === null ? null : this.placed(brace.over),
        )
        const branches = drawn.branches.map((branch, index) => {
          const dashed = brace.certain[index] === false ? ' stroke-dasharray="4 9"' : ''
          return `<path class="marker" stroke-width="3.4"${dashed} d="${branch.line}"/><path class="marker" stroke-width="3.4" d="${branch.head}"/>`
        })
        return `<path class="marker" stroke-width="3.4" d="${drawn.lead}"/>${branches.join('')}`
      })
    return [...arrows, ...braces].join('\n')
  }

  private stickyOf(note: Note): string {
    const at = this.grid.places.get(note)
    if (at === undefined) return ''
    const centre = `${at.w / 2} ${at.h / 2}`
    const paper = note.badge
      ? `<circle cx="${at.w / 2}" cy="${at.h / 2}" r="${at.w / 2}" fill="${COLOURS.actor}" stroke="${INK.text}" stroke-opacity="0.55" stroke-width="1.5"/>`
      : `<rect width="${at.w}" height="${at.h}" rx="1" fill="${COLOURS[note.kind]}" stroke="#000" stroke-opacity="0.12"/>`
    const figure =
      note.kind === 'actor'
        ? `<g transform="translate(${note.badge ? 5 : at.w / 2 - 15} 5) scale(${note.badge ? 22 / 30 : 1})" fill="none" stroke="${INK.text}" stroke-width="1.9" stroke-linecap="round">${FIGURES[note.sticky?.name ?? ''] ?? FIGURES.default}</g>`
        : ''
    return `<g transform="translate(${tenth(at.x)} ${tenth(at.y)}) rotate(${note.tilt.toFixed(2)} ${centre})">${paper}${figure}${lettersOf(note).map(textOf).join('')}</g>`
  }
}

function textOf(letters: Letters): string {
  const centred = letters.align === 'center'
  const x = tenth(centred ? letters.x + letters.w / 2 : letters.x)
  const colour =
    letters.role === 'stamp'
      ? INK.stamp
      : ['whenever', 'via', 'answered', 'cap'].includes(letters.role)
        ? INK.soft
        : INK.text
  const frame =
    letters.role === 'stamp'
      ? `<rect x="${tenth(letters.x)}" y="${tenth(letters.y)}" width="${tenth(letters.w)}" height="${tenth(letters.h)}" rx="3" fill="none" stroke="${INK.stamp}" stroke-width="1.5"/>`
      : ''
  const lines = letters.lines
    .map((line, index) => {
      const y = tenth(
        letters.y +
          letters.size * letters.lineHeight * index +
          letters.size * ASCENT +
          (letters.role === 'stamp' ? 2 : 0),
      )
      return `<tspan x="${x}" y="${y}">${escaped(line)}</tspan>`
    })
    .join('')
  return `${frame}<text font-family='${FONTS[letters.font]}' font-size="${letters.size}"${letters.role === 'stamp' || letters.role === 'bang' ? ' font-weight="700"' : ''} fill="${colour}"${centred ? ' text-anchor="middle"' : ''}>${lines}</text>`
}

function tenth(value: number): number {
  return Math.round(value * 10) / 10
}
