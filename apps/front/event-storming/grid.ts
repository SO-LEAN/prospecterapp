import { lean } from './marker.ts'
import { HEAD, type Lane, type Note, SIZE, type Step, type Strip, VIEW, type Wall } from './wall.ts'

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
}

/** The room above the first lane, for the wall's title and the arrow of time. */
export const TOP = 112
const LANE_HEAD = 26
const ROW_GAP: Record<Density, number> = { roomy: 34, compact: 20 }
const LANE_LEAST_HEIGHT = 126
/** What a step that waits for an event needs right of it: the arrow and the room of its head. */
const AFTER_AN_EVENT = SIZE.sticky + SIZE.arrow + 30
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
    }
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
    return (
      Math.min(...step.notes.map((note) => this.shapeOf(note).y), ...step.strips.map((strip) => strip.y)) -
      step.headroom
    )
  }

  private bottomOf(step: Step): number {
    return Math.max(
      ...step.notes.map((note) => this.shapeOf(note).y + this.shapeOf(note).h),
      ...step.strips.map((strip) => strip.y + strip.h),
    )
  }

  /** The first post-it of the wall for each event a step waits for. */
  private makersOf(step: Step): Note[] {
    return step.waitsFor
      .map((id) => this.wall.notes.find((note) => note.sticky?.id === id))
      .filter((note): note is Note => note !== undefined)
  }

  /** Lays a lane's rows out from its top, and returns its height. */
  private placeTheLane(lane: Lane, top: number): number {
    const rows = this.rowsOf(lane)
    let y = top + LANE_HEAD
    for (const row of rows) {
      row.y = y - Math.min(...row.steps.map((step) => this.topOf(step)))
      y = row.y + Math.max(...row.steps.map((step) => this.bottomOf(step))) + ROW_GAP[this.density]
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
   * steps another lane starts on top, then each actor's group, then the steps no actor sends, then the steps a
   * reaction of this lane starts, right of the event they wait for.
   */
  private rowsOf(lane: Lane): Row[] {
    const rows: Row[] = []
    const waiting = lane.steps.filter((step) => this.makersOf(step).length > 0)
    const far = waiting.filter((step) => this.makersOf(step).every((maker) => maker.step.lane !== lane.name))
    const near = waiting.filter((step) => !far.includes(step))
    const grouped = new Set(lane.groups.flatMap((group) => [...group.reads, ...group.commands]))
    const free = lane.steps
      .filter(
        (step) =>
          !waiting.includes(step) && !grouped.has(step) && !lane.groups.some((group) => group.solo === step),
      )
      .sort(this.byRule)
    for (const step of far) this.putAfterItsMakers(step, rows, 'far')
    this.stackTheGroups(lane, rows)
    for (const step of free) this.putAfterItsMakers(step, rows, 'free')
    for (const step of near) this.putAfterItsMakers(step, rows, 'near')
    return rows
  }

  private stackTheGroups(lane: Lane, rows: Row[]): void {
    for (const group of lane.groups) {
      const commands = [...group.commands].sort(this.byRule)
      if (group.solo !== null) {
        this.stepX.set(group.solo, SIZE.margin)
        rows.push({ steps: [group.solo], right: SIZE.margin + group.solo.width, y: 0 })
      }
      for (let index = 0; index < Math.max(group.reads.length, commands.length); index++) {
        const read = group.reads[index]
        const command = commands[index]
        if (read !== undefined) this.stepX.set(read, VIEW)
        if (command !== undefined) this.stepX.set(command, HEAD + command.shift - (command.main?.x ?? 0))
        const steps = [read, command].filter((step): step is Step => step !== undefined)
        rows.push({
          steps,
          right: Math.max(...steps.map((step) => (this.stepX.get(step) ?? 0) + step.width)),
          y: 0,
        })
      }
    }
  }

  private putAfterItsMakers(step: Step, rows: Row[], kind: 'far' | 'near' | 'free'): void {
    const makers = this.makersOf(step)
    const policyAt = step.notes.find((note) => note.kind === 'policy')?.x ?? 0
    let x = Math.max(
      SIZE.margin,
      ...makers.map((maker) => (this.stepX.get(maker.step) ?? 0) + maker.x + AFTER_AN_EVENT - policyAt),
    )
    let row: Row | undefined
    if (kind === 'near') {
      row = rows.find((candidate) => makers.some((maker) => candidate.steps.includes(maker.step)))
      if (row !== undefined) x = Math.max(x, row.right + SIZE.gap)
    } else if (kind === 'far') {
      row = rows.find((candidate) => candidate.right + SIZE.gap <= x)
    }
    if (row === undefined) {
      row = { steps: [], right: 0, y: 0 }
      rows.push(row)
    }
    this.stepX.set(step, x)
    row.steps.push(step)
    row.right = x + step.width
  }

  /** The rows a lower lane hears go last, so that their arrow leaves downwards and crosses no row. */
  private readonly byRule = (a: Step, b: Step): number =>
    Number(this.feeds(a)) - Number(this.feeds(b)) ||
    a.order - b.order ||
    aggregateOf(a).localeCompare(aggregateOf(b)) ||
    nameOf(a).localeCompare(nameOf(b))

  private feeds(step: Step): boolean {
    return this.wall.edges.some(
      (edge) => edge.drawn && edge.from.step === step && edge.to.step.lane !== step.lane,
    )
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
