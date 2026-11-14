import type { Slot } from '../planning'
import { parseDay } from './dates'
import type { CalendarExchange } from './queries'

/** A gesture in the grid lands on a quarter of an hour. */
export const STEP_IN_MINUTES = 15
/** What a click on a free slot plans for, before a drag says otherwise. */
export const USUAL_DURATION_IN_MINUTES = 30
/** The bounds of a planned exchange (ADR 0111). */
const LONGEST_IN_MINUTES = 8 * 60
const MINUTES_IN_A_DAY = 24 * 60
/** Below it, the pointer only clicked: the exchange opens rather than moves. */
export const DRAG_THRESHOLD_PX = 4

/** Where a pointer stands in the grid: the day under it, and the minute in that day when the grid has hours. */
export type Spot = { day: Date; minutes: number | null }

/** The day of a cell or of a column of the grid, written on it as `data-day`. */
export const DAY_ATTRIBUTE = 'data-day'

/** The cell or the column under the pointer, the minute it points at in a column of hours, snapped down. */
export function spotUnder(x: number, y: number): Spot | null {
  const cell = document.elementFromPoint(x, y)?.closest(`[${DAY_ATTRIBUTE}]`)
  const written = cell?.getAttribute(DAY_ATTRIBUTE)
  if (!cell || !written) return null
  return { day: parseDay(written), minutes: cell.hasAttribute('data-hours') ? minutesAt(cell, y) : null }
}

/** The minute a height of a column of hours points at, snapped down to its quarter. */
export function minutesAt(column: Element, y: number): number {
  const { top, height } = column.getBoundingClientRect()
  const minutes = ((y - top) / height) * MINUTES_IN_A_DAY
  return clamp(snapDown(minutes), 0, MINUTES_IN_A_DAY - STEP_IN_MINUTES)
}

/** A day at a minute from its midnight, in the time of the browser. */
export function atMinutes(day: Date, minutes: number): Date {
  return new Date(day.getFullYear(), day.getMonth(), day.getDate(), 0, minutes)
}

export function minutesOfTheDay(moment: Date): number {
  return moment.getHours() * 60 + moment.getMinutes()
}

/**
 * Where an exchange dragged lands: at the minute pointed at less where it was grabbed in it; on a day of the month,
 * at its own hour.
 */
export function landing(occurredAt: Date, spot: Spot, grabbedMinutes: number): Date {
  if (spot.minutes === null) return atMinutes(spot.day, minutesOfTheDay(occurredAt))
  const latestStart = MINUTES_IN_A_DAY - STEP_IN_MINUTES
  return atMinutes(spot.day, clamp(snapDown(spot.minutes - grabbedMinutes), 0, latestStart))
}

/** The duration of an exchange stretched to the minute pointed at: a quarter at least, 8 hours at most, in its day. */
export function stretched(occurredAt: Date, endMinutes: number): number {
  const start = minutesOfTheDay(occurredAt)
  const end = snapDown(endMinutes) + STEP_IN_MINUTES
  return clamp(end - start, STEP_IN_MINUTES, Math.min(LONGEST_IN_MINUTES, MINUTES_IN_A_DAY - start))
}

/** A free slot chosen from a quarter to another, either way, through the last; clicked, half an hour. */
export function slotBetween(day: Date, from: number, to: number): Slot {
  const start = Math.min(from, to)
  const end = Math.max(from, to) + STEP_IN_MINUTES
  return {
    startsAt: atMinutes(day, start),
    durationInMinutes: from === to ? USUAL_DURATION_IN_MINUTES : Math.min(end - start, LONGEST_IN_MINUTES),
  }
}

/** The calendar as it shows while an exchange is dragged or stretched: at its new moment, for its new duration. */
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
