import { lean, type Route } from './marker.ts'
import {
  type Edge,
  HEAD,
  type Lane,
  type Note,
  SIZE,
  type Step,
  type Strip,
  VIEW,
  type Wall,
} from './wall.ts'

/** Roomy rows show what a use case asks as named strips; compact rows show it as small pink squares. */
export type Density = 'roomy' | 'compact'

export interface Place {
  x: number
  y: number
  w: number
  h: number
}

/** A use case's name under its squares, in a folded lane. */
export interface FoldLabel {
  x: number
  y: number
  text: string
}

/** Where a lane stands, and how it reads folded: its steps as squares, each named, relative to its top. */
export interface LanePlace {
  top: number
  height: number
  /** As far as its rightmost post-it, and the margin of the wall. */
  width: number
  rows: number
  useCases: number
  foldedHeight: number
  folded: Map<Note, Place>
  labels: FoldLabel[]
}

/** The wall laid out at real size, every lane unfolded. */
export interface Grid {
  density: Density
  width: number
  height: number
  places: Map<Note, Place>
  strips: Map<Strip, Place>
  lanes: Map<Lane, LanePlace>
  /** The rank of each step's row on the wall, from the top. */
  rows: Map<Step, number>
  /** The way of each arrow of a reaction that ends in a lower row. */
  routes: Map<Edge, Route>
}

/** The room above the first lane, for the wall's title and the arrow of time. */
export const TOP = 112
const LANE_HEAD = 26
const ROW_GAP: Record<Density, number> = { roomy: 34, compact: 20 }
const LANE_LEAST_HEIGHT = 126
/** What a reaction needs between the post-it it hears and its own: the arrow and the room of its head. */
const AFTER_ITS_CAUSE = SIZE.arrow + 30
/** How far left of its end a routed arrow runs down its corridor (`before`), and how far right of its start it drops (`drop`). */
const CORRIDOR = { before: 40, drop: 18 } as const
const WALL_LEAST_WIDTH = 1500
const WALL_RIGHT_MARGIN = 150
const WALL_BOTTOM_MARGIN = 30
/** The squares of a folded lane, and how far its band runs before it wraps. */
const FOLDED = {
  square: 17,
  pitch: 20,
  letter: 7.4,
  room: 22,
  run: 1340,
  lineHeight: 58,
  first: 30,
  label: 51,
  least: 96,
}
/** The pink squares of compact rows. */
const DOT = { size: 25, pitch: 29, left: 9, below: 5 }

interface Row {
  steps: Step[]
  right: number
  y: number
  bottom: number
  /** Its rank on the wall, from the top. */
  rank: number
}

export function gridOf(wall: Wall, density: Density): Grid {
  return new GridUnderConstruction(wall, density).built()
}

class GridUnderConstruction {
  private readonly wall: Wall
  private readonly density: Density
  private readonly stepX = new Map<Step, number>()
  private readonly places = new Map<Note, Place>()
  private readonly strips = new Map<Strip, Place>()
  private readonly lanes = new Map<Lane, LanePlace>()
  private readonly rowRanks = new Map<Step, number>()
  /** The wall's rows so far, from the top. */
  private readonly rows: Row[] = []
  private readonly corridorOf = new Map<Edge, number>()

  constructor(wall: Wall, density: Density) {
    this.wall = wall
    this.density = density
  }

  built(): Grid {
    let y = TOP
    for (const lane of this.wall.lanes) y += this.placeTheLane(lane, y)
    const rights = [...this.places.values(), ...this.strips.values()].map((place) => place.x + place.w)
    return {
      density: this.density,
      width: Math.max(WALL_LEAST_WIDTH, ...rights) + WALL_RIGHT_MARGIN,
      height: y + WALL_BOTTOM_MARGIN,
      places: this.places,
      strips: this.strips,
      lanes: this.lanes,
      rows: this.rowRanks,
      routes: this.routes(),
    }
  }

  /** Each arrow to a lower row leaves its start down into the gap under its row, then runs down its corridor. */
  private routes(): Map<Edge, Route> {
    const routes = new Map<Edge, Route>()
    for (const edge of this.wall.edges.filter((edge) => edge.drawn)) {
      const from = this.rankOf(edge.from.step)
      if (this.rankOf(edge.to.step) <= from) continue
      const start = this.places.get(edge.from)
      const row = this.rows[from]
      if (start === undefined || row === undefined) continue
      const corridor = this.corridorOf.get(edge) ?? (this.places.get(edge.to)?.x ?? 0) - CORRIDOR.before
      const out = start.x + start.w + CORRIDOR.drop
      routes.set(edge, {
        out,
        corridor: Math.max(corridor, out),
        drop: row.bottom + ROW_GAP[this.density] / 2 - start.y,
      })
    }
    return routes
  }

  /** The size of a post-it in this density: a port of compact rows is a small square under its use case. */
  private shapeOf(note: Note): Place {
    if (this.density === 'compact' && note.port && note.owner !== null) {
      const { owner } = note
      return {
        x: owner.x + DOT.left + note.slot * DOT.pitch,
        y: owner.y + SIZE.sticky - DOT.below,
        w: DOT.size,
        h: DOT.size,
      }
    }
    return { x: note.x, y: note.y, w: note.w, h: note.h }
  }

  private topOf(step: Step): number {
    return Math.min(...step.notes.map((note) => this.shapeOf(note).y), ...step.strips.map((strip) => strip.y))
  }

  private bottomOf(step: Step): number {
    return Math.max(
      ...step.notes.map((note) => this.shapeOf(note).y + this.shapeOf(note).h),
      ...step.strips.map((strip) => strip.y + strip.h),
    )
  }

  /** The arrows of the reactions that start a step, from the post-its of other steps. */
  private causesOf(step: Step): Edge[] {
    return this.wall.edges.filter((edge) => edge.drawn && edge.to.step === step && edge.from.step !== step)
  }

  private rankOf(step: Step): number {
    return this.rowRanks.get(step) ?? -1
  }

  /** Lays a lane's rows out from its top, and returns its height. */
  private placeTheLane(lane: Lane, top: number): number {
    const rows = this.rowsOf(lane)
    let y = top + LANE_HEAD
    for (const row of rows) {
      row.y = y - Math.min(...row.steps.map((step) => this.topOf(step)))
      row.bottom = row.y + Math.max(...row.steps.map((step) => this.bottomOf(step)))
      y = row.bottom + ROW_GAP[this.density]
      for (const step of row.steps) this.placeTheStep(step, row.y)
    }
    const height = Math.max(LANE_LEAST_HEIGHT, y - top + 8)
    this.lanes.set(lane, {
      top,
      height,
      width: this.rightOf(lane.steps.flatMap((step) => step.notes)) + WALL_RIGHT_MARGIN,
      rows: rows.length,
      useCases: lane.steps.filter((step) => step.main !== null).length,
      ...this.foldedBandOf(rows),
    })
    return height
  }

  private rightOf(notes: Note[]): number {
    const places = notes.map((note) => this.places.get(note)).filter((place) => place !== undefined)
    return Math.max(...places.map((place) => place.x + place.w))
  }

  private placeTheStep(step: Step, rowY: number): void {
    const x = this.stepX.get(step) ?? 0
    for (const note of step.notes) {
      const shape = this.shapeOf(note)
      this.places.set(note, {
        ...shape,
        x: x + shape.x,
        y: rowY + shape.y + lean(`${(note.owner ?? note).seed}y`, 3.5),
      })
    }
    for (const strip of step.strips) {
      this.strips.set(strip, { x: x + strip.x, y: rowY + strip.y, w: strip.w, h: strip.h })
    }
  }

  /**
   * The horizontal axis is time, so only a cause moves a step to the right. Independent steps stack in rows: the
   * steps another lane starts on top, then the views and the commands of the lane's actors, then the steps no actor
   * sends, then the steps a reaction of this lane starts. A reaction stands right of its cause, in its row or lower.
   */
  private rowsOf(lane: Lane): Row[] {
    const rows: Row[] = []
    const waiting = lane.steps.filter((step) => this.causesOf(step).length > 0)
    const far = waiting.filter((step) =>
      this.causesOf(step).every((cause) => cause.from.step.lane !== lane.name),
    )
    const near = waiting.filter((step) => !far.includes(step))
    const blocked = new Set(lane.blocks.flatMap((block) => [block.view, ...block.commands]))
    const free = lane.steps.filter((step) => !waiting.includes(step) && !blocked.has(step)).sort(this.byRule)
    for (const step of far) this.putAfterItsCauses(step, rows)
    this.stackTheBlocks(lane, rows)
    for (const step of free) this.putInRow(step, this.newRow(rows), SIZE.margin)
    for (const step of this.byTheirCauses(near)) this.putAfterItsCauses(step, rows)
    return rows
  }

  /** The reactions of a lane, each after the reactions of the lane that start it. */
  private byTheirCauses(steps: Step[]): Step[] {
    const ordered: Step[] = []
    const left = [...steps]
    while (left.length > 0) {
      const ready = left.find((step) =>
        this.causesOf(step).every((cause) => !left.includes(cause.from.step) || cause.from.step === step),
      )
      const next = ready ?? left[0]
      if (next === undefined) break
      ordered.push(next)
      left.splice(left.indexOf(next), 1)
    }
    return ordered
  }

  private newRow(rows: Row[]): Row {
    const row: Row = { steps: [], right: 0, y: 0, bottom: 0, rank: this.rows.length }
    this.rows.push(row)
    rows.push(row)
    return row
  }

  private putInRow(step: Step, row: Row, x: number): void {
    this.stepX.set(step, x)
    this.rowRanks.set(step, row.rank)
    row.steps.push(step)
    row.right = Math.max(row.right, x + this.widthOf(step))
  }

  /** As far right as its post-its and their paper reach, from the step's left. */
  private widthOf(step: Step): number {
    return Math.max(
      step.width,
      ...step.notes.map((note) => note.x + note.w),
      ...step.strips.map((strip) => strip.x + strip.w),
    )
  }

  /**
   * A reaction stands right of what it hears, with room for its arrow: in the row of its lowest cause, or in a lower
   * row. It takes the place nearest the left.
   */
  private putAfterItsCauses(step: Step, rows: Row[]): void {
    const causes = this.causesOf(step)
    const least = Math.max(
      SIZE.margin,
      ...causes.map((cause) => this.rightOfNote(cause.from) + AFTER_ITS_CAUSE - cause.to.x),
    )
    const lowest = Math.max(...causes.map((cause) => this.rankOf(cause.from.step)))
    const candidates = rows.filter((row) => row.rank >= lowest)
    const placed = [...candidates, null].map((row) => ({
      row,
      x: Math.max(least, row === null ? SIZE.margin : row.right + SIZE.gap),
    }))
    const best = placed.reduce((one, other) => (other.x < one.x ? other : one))
    const row = best.row ?? this.newRow(rows)
    this.putInRow(step, row, best.x)
    for (const cause of causes.filter((cause) => this.rankOf(cause.from.step) < row.rank)) {
      this.corridorOf.set(cause, best.x + cause.to.x - CORRIDOR.before)
    }
  }

  private rightOfNote(note: Note): number {
    return (this.stepX.get(note.step) ?? 0) + note.x + note.w
  }

  /** Each view in its column, its first command beside it, its other commands under it, in their column. */
  private stackTheBlocks(lane: Lane, rows: Row[]): void {
    for (const block of lane.blocks) {
      const commands = [...block.commands].sort(this.byRule)
      for (let index = 0; index < Math.max(1, commands.length); index++) {
        const row = this.newRow(rows)
        const view = index === 0 ? block.view : null
        const command = commands[index]
        if (view !== null) this.putInRow(view, row, VIEW - (view.main?.x ?? 0))
        if (command !== undefined) this.putInRow(command, row, HEAD - (command.main?.x ?? 0))
      }
    }
  }

  /** The rows another step hears go last, so that their arrows leave downwards and cross few rows. */
  private readonly byRule = (a: Step, b: Step): number =>
    Number(this.feeds(a)) - Number(this.feeds(b)) ||
    a.order - b.order ||
    aggregateOf(a).localeCompare(aggregateOf(b)) ||
    nameOf(a).localeCompare(nameOf(b))

  private feeds(step: Step): boolean {
    return this.wall.edges.some((edge) => edge.drawn && edge.from.step === step && edge.to.step !== step)
  }

  /** A folded lane is a table of contents: each step's squares and its use case's name under them. */
  private foldedBandOf(rows: Row[]): Pick<LanePlace, 'foldedHeight' | 'folded' | 'labels'> {
    const folded = new Map<Note, Place>()
    const labels: FoldLabel[] = []
    let at = SIZE.margin
    let line = 0
    for (const step of rows.flatMap((row) => row.steps)) {
      const text = nameOf(step) || (step.notes[0]?.label ?? '')
      const width = Math.max(step.notes.length * FOLDED.pitch, text.length * FOLDED.letter) + FOLDED.room
      if (at + width > SIZE.margin + FOLDED.run) {
        at = SIZE.margin
        line++
      }
      step.notes.forEach((note, index) => {
        const y = FOLDED.first + line * FOLDED.lineHeight
        folded.set(note, { x: at + index * FOLDED.pitch, y, w: FOLDED.square, h: FOLDED.square })
      })
      labels.push({ x: at, y: FOLDED.label + line * FOLDED.lineHeight, text })
      at += width
    }
    return {
      foldedHeight: Math.max(FOLDED.least, FOLDED.first + (line + 1) * FOLDED.lineHeight + 8),
      folded,
      labels,
    }
  }
}

function aggregateOf(step: Step): string {
  return step.notes.find((note) => note.kind === 'aggregate')?.label ?? ''
}

/** The name of a step: its use case, or else its first use case of this wall. */
export function nameOf(step: Step): string {
  const useCase = step.notes.find(
    (note) => ['command', 'readModel', 'policy'].includes(note.kind) && !note.foreign,
  )
  return step.main?.label ?? useCase?.label ?? ''
}
