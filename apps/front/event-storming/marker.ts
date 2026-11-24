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
