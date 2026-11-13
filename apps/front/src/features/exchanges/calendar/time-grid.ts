import type { Exchange } from '../queries'

/** A noted exchange has no duration: it takes the room of half an hour, to be read. */
const SHORTEST_SHOWN_IN_MINUTES = 30
const MINUTES_IN_AN_HOUR = 60
const MINUTES_IN_A_DAY = 24 * MINUTES_IN_AN_HOUR

/** Where an exchange stands in its day, in hours from midnight, and the lane it takes among those it overlaps. */
export type Placed<Item> = { item: Item; start: number; length: number; lane: number; lanes: number }

type Timed = { exchange: Pick<Exchange, 'occurredAt' | 'durationInMinutes'> }

/**
 * The exchanges of a day side by side when they overlap: each in the first lane free at its start, the lanes of a
 * group of overlapping exchanges sharing the width; in the time of the browser.
 */
export function placeInTheDay<Item extends Timed>(items: Item[]): Placed<Item>[] {
  const timed = items
    .map((item) => ({ item, ...minutesOf(item) }))
    .sort((one, other) => one.from - other.from || other.until - one.until)
  const placed: Placed<Item>[] = []
  let group = aNewGroup<Item>()

  for (const { item, from, until } of timed) {
    if (from >= group.until) group = aNewGroup<Item>()
    const free = group.lanes.findIndex((laneUntil) => laneUntil <= from)
    const lane = free === -1 ? group.lanes.length : free
    group.lanes[lane] = until
    group.until = Math.max(group.until, until)
    const one = {
      item,
      start: from / MINUTES_IN_AN_HOUR,
      length: (until - from) / MINUTES_IN_AN_HOUR,
      lane,
      lanes: 0,
    }
    group.members.push(one)
    for (const member of group.members) member.lanes = group.lanes.length
    placed.push(one)
  }
  return placed
}

/** The exchanges overlapping one another, the moment each lane is free again, the moment all of them are. */
type Group<Item> = { lanes: number[]; members: Placed<Item>[]; until: number }

function aNewGroup<Item>(): Group<Item> {
  return { lanes: [], members: [], until: Number.NEGATIVE_INFINITY }
}

/** The moment in hours from midnight: where the line of now stands. */
export function hoursFromMidnight(moment: Date): number {
  return moment.getHours() + moment.getMinutes() / MINUTES_IN_AN_HOUR
}

function minutesOf({ exchange }: Timed): { from: number; until: number } {
  const start = new Date(exchange.occurredAt)
  const from = start.getHours() * MINUTES_IN_AN_HOUR + start.getMinutes()
  const shown = Math.max(exchange.durationInMinutes ?? 0, SHORTEST_SHOWN_IN_MINUTES)

  return { from, until: Math.min(from + shown, MINUTES_IN_A_DAY) }
}
