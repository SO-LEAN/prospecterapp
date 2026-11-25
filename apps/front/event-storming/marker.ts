/**
 * The hand of the facilitator: how much a post-it leans, how a marker line wobbles. Each function is self-contained
 * (it calls only the others of this file and Math), because the page also writes their source in its script.
 */

/** A post-it leans by a hash of its name, never by chance, so that two generations give the same files (FNV-1a). */
export function hashOf(name: string): number {
  let hash = 2166136261
  for (let index = 0; index < name.length; index++) {
    hash ^= name.charCodeAt(index)
    hash = Math.imul(hash, 16777619)
  }
  return hash >>> 0
}

/** A value between -amplitude and +amplitude, the same for the same name. */
export function lean(name: string, amplitude: number): number {
  return ((hashOf(name) % 2001) / 1000 - 1) * amplitude
}

export interface Point {
  x: number
  y: number
}

/** A line drawn by hand: a few slight waves, one every 260 px or so. */
export function wobbly(from: Point, to: Point, seed: string): string {
  const waves = Math.max(2, Math.round(Math.hypot(to.x - from.x, to.y - from.y) / 260))
  let path = `M${from.x},${from.y}`
  for (let wave = 1; wave <= waves; wave++) {
    const share = wave / waves
    const x = from.x + (to.x - from.x) * share
    const y = from.y + (to.y - from.y) * share
    const control = `${(x - (to.x - from.x) / waves / 2).toFixed(1)},${(y + lean(seed + wave, 3.2)).toFixed(1)}`
    path += ` Q${control} ${x.toFixed(1)},${(y + lean(`${seed}${wave}e`, 1.4)).toFixed(1)}`
  }
  return path
}

/** A post-it where the wall shows it, and the seed of its lean. */
export interface Placed {
  x: number
  y: number
  w: number
  h: number
  seed: string
}

export interface Arrow {
  curve: string
  head: string
  /** Where "maybe" is written on an arrow that happens only under a condition. */
  maybe: Point | null
}

/** A reaction's marker arrow: from the right of a post-it to the left of the next, slightly bent. */
export function arrowOf(from: Placed, to: Placed, certain: boolean): Arrow {
  const x1 = from.x + from.w + 3
  const y1 = Math.round((from.y + from.h / 2 + lean(`${from.seed}out`, 5)) * 10) / 10
  const x2 = to.x - 9
  const y2 = Math.round((to.y + to.h / 2 + lean(`${to.seed}in`, 5)) * 10) / 10
  const reach = Math.max(34, Math.min(180, Math.abs(x2 - x1) * 0.5))
  const bend = lean(from.seed + to.seed, 9)
  const c1 = { x: x1 + reach, y: y1 + bend }
  const c2 = { x: x2 - reach, y: y2 - bend }
  const angle = Math.atan2(y2 - c2.y, x2 - c2.x)
  const barb = (turn: number): string =>
    `M${x2},${y2} L${(x2 - 13 * Math.cos(angle + turn)).toFixed(1)},${(y2 - 13 * Math.sin(angle + turn)).toFixed(1)}`
  return {
    curve: `M${x1},${y1} C${c1.x.toFixed(1)},${c1.y.toFixed(1)} ${c2.x.toFixed(1)},${c2.y.toFixed(1)} ${x2},${y2}`,
    head: `${barb(0.48)} ${barb(-0.48)}`,
    maybe: certain ? null : { x: (x1 + x2) / 2, y: (y1 + y2) / 2 - 9 },
  }
}

export interface Branch {
  line: string
  head: string
  /** The straight path the dashed line's condition shows on, wide enough for the mouse and the focus. */
  target: string
}

export interface Brace {
  lead: string
  branches: Branch[]
}

/**
 * The marker line from a view that branches to each command it offers: it leaves the view on its right, runs down a
 * spine, and ends in a tick at each command's actor.
 */
export function braceOf(from: Placed, ends: Placed[], spine: number): Brace {
  const seed = `${from.seed}${spine}`
  const ys = ends.map((end) => Math.round((end.y + end.h / 2 + lean(`${end.seed}${seed}in`, 8)) * 10) / 10)
  const y0 = from.y + from.h / 2
  const lead = wobbly({ x: from.x + from.w + 4, y: y0 }, { x: spine, y: y0 }, seed)
  const down = wobbly({ x: spine, y: Math.min(y0, ...ys) }, { x: spine, y: Math.max(y0, ...ys) }, `${seed}s`)
  const branches = ends.map((end, index) => {
    const x = end.x - 9
    const y = ys[index] ?? end.y
    return {
      line: wobbly({ x: spine, y }, { x, y }, end.seed),
      head: `M${x},${y} l-9,-6 M${x},${y} l-9,6`,
      target: `M${spine},${y} L${x},${y}`,
    }
  })
  return { lead: `${lead} ${down}`, branches }
}
