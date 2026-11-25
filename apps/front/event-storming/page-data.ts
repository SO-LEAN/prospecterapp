import { type Density, type FoldLabel, type Grid, nameOf, type Place, TOP } from './grid.ts'
import { cardOf, KIND_NAMES, storyOf } from './story.ts'
import { HEAD, type Kind, type Note, sentenceOf, type Wall } from './wall.ts'

/**
 * What the page's script needs of the wall, laid out at generation: each post-it's place in both densities and in
 * its folded lane, its story and its card. The script only moves the camera and the post-its, and lights them.
 */
export interface WallData {
  context: string
  label: string
  top: number
  width: Record<Density, number>
  notes: NoteData[]
  edges: EdgeData[]
  braces: BraceData[]
  strips: StripData[]
  lanes: LaneData[]
  actors: number[]
}

export interface NoteData {
  slug: string
  kind: Kind
  label: string
  lane: number
  seed: string
  port: boolean
  badge: boolean
  places: Record<Density, Place>
  folded: Place
  story: number[]
  card: string
  search: string
  jumps: JumpData[]
}

export interface JumpData {
  to: number | null
  back: boolean
  tip: string
}

export interface EdgeData {
  from: number
  to: number
  certain: boolean
  tip: string | null
}

export interface BraceData {
  from: number
  lane: number
  spine: number
  ends: number[]
  commands: number[]
  certain: boolean[]
  tips: (string | null)[]
}

export interface StripData {
  note: number
  lane: number
  context: string
  tilt: number
  places: Record<Density, Place>
}

export interface LaneData {
  name: string
  label: string
  useCases: number
  steps: string[]
  top: Record<Density, number>
  height: Record<Density, number>
  rows: Record<Density, number>
  foldedHeight: number
  labels: FoldLabel[]
}

/** The room between the spines of the lines that leave one lane's views. */
const SPINE = { before: 50, shift: 11 }

export function dataOf(wall: Wall, grids: Record<Density, Grid>): WallData {
  const index = (note: Note): number => wall.notes.indexOf(note)
  const laneOf = (note: Note): number => wall.lanes.findIndex((lane) => lane.name === note.step.lane)
  return {
    context: wall.context,
    label: sentenceOf(wall.context),
    top: TOP,
    width: { roomy: grids.roomy.width, compact: grids.compact.width },
    notes: wall.notes.map((note) => ({
      slug: note.slug,
      kind: note.kind,
      label: note.label,
      lane: laneOf(note),
      seed: note.seed,
      port: note.port,
      badge: note.badge,
      places: { roomy: placeIn(grids.roomy, note), compact: placeIn(grids.compact, note) },
      folded: foldedIn(wall, grids.roomy, note),
      story: [...storyOf(wall, note)].map(index),
      card: cardOf(wall, note),
      search: searchOf(note),
      jumps: note.jumps.map((jump) => ({
        to: jump.to === null ? null : index(jump.to),
        back: jump.back,
        tip: jump.tip,
      })),
    })),
    edges: wall.edges
      .filter((edge) => edge.drawn)
      .map((edge) => ({
        from: index(edge.from),
        to: index(edge.to),
        certain: edge.certain,
        tip: edge.certain ? null : `Maybe: only ${edge.when ?? 'in some states'}`,
      })),
    braces: wall.brackets.map((bracket) => ({
      from: index(bracket.from),
      lane: wall.lanes.indexOf(bracket.lane),
      spine: HEAD - SPINE.before - bracket.shift * SPINE.shift,
      ends: bracket.items.map((item) => index(item.to)),
      commands: bracket.items.map((item) => index(item.command)),
      certain: bracket.items.map((item) => item.certain),
      tips: bracket.items.map((item) => item.tip),
    })),
    strips: wall.steps.flatMap((step) =>
      step.strips.map((strip) => ({
        note: index(strip.note),
        lane: laneOf(strip.note),
        context: strip.context,
        tilt: strip.tilt,
        places: { roomy: stripIn(grids.roomy, strip), compact: stripIn(grids.compact, strip) },
      })),
    ),
    lanes: wall.lanes.map((lane) => {
      const roomy = laneIn(grids.roomy, lane)
      const compact = laneIn(grids.compact, lane)
      return {
        name: lane.name,
        label: lane.label,
        useCases: roomy.useCases,
        steps: lane.steps.map(nameOf).filter((name) => name !== ''),
        top: { roomy: roomy.top, compact: compact.top },
        height: { roomy: roomy.height, compact: compact.height },
        rows: { roomy: roomy.rows, compact: compact.rows },
        foldedHeight: roomy.foldedHeight,
        labels: roomy.labels.map((label) => ({ ...label, y: label.y })),
      }
    }),
    actors: firstOfEachActor(wall).map(index),
  }
}

function placeIn(grid: Grid, note: Note): Place {
  const place = grid.places.get(note)
  if (place === undefined) throw new Error(`${note.label} has no place on the grid`)
  return rounded(place)
}

function foldedIn(wall: Wall, grid: Grid, note: Note): Place {
  const lane = wall.lanes.find((candidate) => candidate.name === note.step.lane)
  const place = lane === undefined ? undefined : grid.lanes.get(lane)?.folded.get(note)
  if (place === undefined) throw new Error(`${note.label} has no place in its folded lane`)
  return place
}

function stripIn(grid: Grid, strip: Parameters<Grid['strips']['get']>[0]): Place {
  const place = grid.strips.get(strip)
  if (place === undefined) throw new Error('A strip has no place on the grid')
  return rounded(place)
}

function laneIn(grid: Grid, lane: Wall['lanes'][number]): NonNullable<ReturnType<Grid['lanes']['get']>> {
  const place = grid.lanes.get(lane)
  if (place === undefined) throw new Error(`The lane ${lane.name} has no place on the grid`)
  return place
}

/** A place to a tenth of a point: enough to draw, and the same from one generation to the next. */
function rounded(place: Place): Place {
  const tenth = (value: number): number => Math.round(value * 10) / 10
  return { x: tenth(place.x), y: tenth(place.y), w: place.w, h: place.h }
}

/** What the search reads of a post-it: its label, its kind, its class, its feature's title, what it presents. */
function searchOf(note: Note): string {
  const { sticky } = note
  return [note.label, KIND_NAMES[note.kind], sticky?.name, sticky?.class, sticky?.feature, sticky?.detail]
    .filter((part): part is string => typeof part === 'string')
    .join(' ')
    .toLowerCase()
}

function firstOfEachActor(wall: Wall): Note[] {
  const seen = new Set<string>()
  return wall.notes.filter((note) => {
    if (note.kind !== 'actor' || seen.has(note.slug)) return false
    seen.add(note.slug)
    return true
  })
}
