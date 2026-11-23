import { LONGEST, type Slot } from '../planning'
import { parseDay } from './dates'
import type { CalendarExchange } from './queries'

/** A gesture in the grid lands on a quarter hour. */
export const STEP_IN_MINUTES = 15
/** The duration that a click on a free slot plans, unless a drag sets another one. */
export const USUAL_DURATION_IN_MINUTES = 30
const MINUTES_IN_A_DAY = 24 * 60
/** Below this distance, the pointer only clicked. The exchange opens instead of moving. */
export const DRAG_THRESHOLD_PX = 4

/** Where a pointer is in the grid: the day under it, and the minute in that day when the grid has hours. */
export type Spot = { day: Date; minutes: number | null }

/** The day of a grid's cell or column, written on it as `data-day`. */
export const DAY_ATTRIBUTE = 'data-day'

/** The cell or the column under the pointer. In a column of hours, also the minute it points at, snapped down. */
export function spotUnder(x: number, y: number): Spot | null {
  const cell = document.elementFromPoint(x, y)?.closest(`[${DAY_ATTRIBUTE}]`)
  const written = cell?.getAttribute(DAY_ATTRIBUTE)
  if (!cell || !written) return null
  return { day: parseDay(written), minutes: cell.hasAttribute('data-hours') ? minutesAt(cell, y) : null }
}

/** The minute that a height in a column of hours points at, snapped down to its quarter hour. */
export function minutesAt(column: Element, y: number): number {
  const { top, height } = column.getBoundingClientRect()
  const minutes = ((y - top) / height) * MINUTES_IN_A_DAY
  return clamp(snapDown(minutes), 0, MINUTES_IN_A_DAY - STEP_IN_MINUTES)
}

/** A day at a given minute after its midnight, in the browser's time zone. */
export function atMinutes(day: Date, minutes: number): Date {
  return new Date(day.getFullYear(), day.getMonth(), day.getDate(), 0, minutes)
}

export function minutesOfTheDay(moment: Date): number {
  return moment.getHours() * 60 + moment.getMinutes()
}

/**
 * Where a dragged exchange lands: at the minute pointed at, minus the offset where it was grabbed. On a day of the
 * month view, it keeps its own hour.
 */
export function landing(occurredAt: Date, spot: Spot, grabbedMinutes: number): Date {
  if (spot.minutes === null) return atMinutes(spot.day, minutesOfTheDay(occurredAt))
  const latestStart = MINUTES_IN_A_DAY - STEP_IN_MINUTES
  return atMinutes(spot.day, clamp(snapDown(spot.minutes - grabbedMinutes), 0, latestStart))
}

/** The duration of an exchange stretched to the minute pointed at: a quarter hour at least, 8 hours at most, within its day. */
export function stretched(occurredAt: Date, endMinutes: number): number {
  const start = minutesOfTheDay(occurredAt)
  const end = snapDown(endMinutes) + STEP_IN_MINUTES
  return clamp(end - start, STEP_IN_MINUTES, Math.min(LONGEST, MINUTES_IN_A_DAY - start))
}

/** A free slot chosen from one quarter hour to another, in either direction, including the last one. A click gives half an hour. */
export function slotBetween(day: Date, from: number, to: number): Slot {
  const start = Math.min(from, to)
  const end = Math.max(from, to) + STEP_IN_MINUTES
  return {
    startsAt: atMinutes(day, start),
    durationInMinutes: from === to ? USUAL_DURATION_IN_MINUTES : Math.min(end - start, LONGEST),
  }
}

/** The calendar as it shows while an exchange is dragged or stretched: at its new moment, with its new duration. */
export function withTheGesture(
  items: CalendarExchange[],
  moved: { id: string; occurredAt: Date; durationInMinutes: number | null } | null,
): CalendarExchange[] {
  if (!moved) return items
  return items.map((item) =>
    item.exchange.id === moved.id
      ? {
          ...item,
          exchange: {
            ...item.exchange,
            occurredAt: moved.occurredAt.toISOString(),
            durationInMinutes: moved.durationInMinutes,
          },
        }
      : item,
  )
}

function snapDown(minutes: number): number {
  return Math.floor(minutes / STEP_IN_MINUTES) * STEP_IN_MINUTES
}

function clamp(value: number, lowest: number, highest: number): number {
  return Math.min(Math.max(value, lowest), highest)
}
