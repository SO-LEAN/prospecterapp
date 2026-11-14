import { type PointerEvent, useEffect, useEffectEvent, useRef, useState } from 'react'
import type { Slot } from '../planning'
import {
  DRAG_THRESHOLD_PX,
  landing,
  minutesAt,
  minutesOfTheDay,
  slotBetween,
  spotUnder,
  stretched,
  withTheGesture,
} from './gestures'
import type { CalendarMove } from './mutations'
import type { CalendarExchange } from './queries'

/** Dragged whole to another moment, or stretched by its end to another duration. */
type Handling = 'move' | 'stretch'

type Dragging = {
  item: CalendarExchange
  handling: Handling
  /** Where the pointer went down, to tell a drag from a click. */
  x: number
  y: number
  /** How far into the exchange it was grabbed, in minutes: it lands where the pointer goes, less that. */
  grabbedMinutes: number
  dragged: boolean
  occurredAt: Date
  durationInMinutes: number | null
}

/** A free slot being chosen in a column of hours, from the quarter the pointer went down on to the one it is on. */
type Choosing = { day: Date; from: number; to: number }

type Props = {
  items: CalendarExchange[]
  onMove: (move: CalendarMove) => void
  onChooseSlot?: (slot: Slot) => void
}

/**
 * What the pointer does in the grid: an exchange the API lets move (`_links.move`) dragged to another moment or
 * stretched, shown there while it is; a click on it still opens it; a free slot clicked or dragged in a column of
 * hours, to plan there. Once an exchange is grabbed, the window follows the pointer: the exchange shown elsewhere is
 * drawn again in another column, which the pointer may not be over.
 */
export function useGestures({ items, onMove, onChooseSlot }: Props) {
  const [dragging, setDragging] = useState<Dragging | null>(null)
  const [choosing, setChoosing] = useState<Choosing | null>(null)
  const draggedJustNow = useRef(false)

  const follow = useEffectEvent((event: globalThis.PointerEvent) => {
    if (!dragging) return
    const far = Math.hypot(event.clientX - dragging.x, event.clientY - dragging.y) >= DRAG_THRESHOLD_PX
    if (dragging.dragged || far) setDragging({ ...dragging, dragged: true, ...landed(dragging, event) })
  })

  const drop = useEffectEvent(() => {
    if (!dragging) return
    setDragging(null)
    if (!dragging.dragged) return
    draggedJustNow.current = true
    const { item, occurredAt, durationInMinutes } = dragging
    if (changed(dragging)) onMove({ item, occurredAt, durationInMinutes })
  })

  const grabbing = dragging !== null
  useEffect(() => {
    if (!grabbing) return
    const moved = (event: globalThis.PointerEvent) => follow(event)
    const released = () => drop()
    const leave = () => setDragging(null)
    window.addEventListener('pointermove', moved)
    window.addEventListener('pointerup', released)
    window.addEventListener('pointercancel', leave)
    return () => {
      window.removeEventListener('pointermove', moved)
      window.removeEventListener('pointerup', released)
      window.removeEventListener('pointercancel', leave)
    }
  }, [grabbing])

  const grab = (item: CalendarExchange, handling: Handling) => (event: PointerEvent<HTMLElement>) => {
    draggedJustNow.current = false
    if (!item.exchange._links.move || event.button !== 0) return
    event.stopPropagation()
    const occurredAt = new Date(item.exchange.occurredAt)
    const spot = spotUnder(event.clientX, event.clientY)
    setDragging({
      item,
      handling,
      x: event.clientX,
      y: event.clientY,
      grabbedMinutes: spot?.minutes == null ? 0 : spot.minutes - minutesOfTheDay(occurredAt),
      dragged: false,
      occurredAt,
      durationInMinutes: item.exchange.durationInMinutes ?? null,
    })
  }

  /** A drag may end with a click on what was dragged: it must not open it; the next press is a click again. */
  const unlessDraggedJustNow = (click: () => void) => {
    if (!draggedJustNow.current) click()
    draggedJustNow.current = false
  }

  const startChoosing = (day: Date) => (event: PointerEvent<HTMLElement>) => {
    if (!onChooseSlot || event.button !== 0 || event.target !== event.currentTarget) return
    event.currentTarget.setPointerCapture?.(event.pointerId)
    const minutes = minutesAt(event.currentTarget, event.clientY)
    setChoosing({ day, from: minutes, to: minutes })
  }

  const choose = (event: PointerEvent<HTMLElement>) => {
    if (choosing) setChoosing({ ...choosing, to: minutesAt(event.currentTarget, event.clientY) })
  }

  const chosen = () => {
    if (!choosing) return
    setChoosing(null)
    onChooseSlot?.(slotBetween(choosing.day, choosing.from, choosing.to))
  }

  const shownMoved = dragging?.dragged ? { id: dragging.item.exchange.id, ...dragging } : null

  return {
    /** The exchanges as they show, the one dragged where it is being dragged. */
    shown: withTheGesture(items, shownMoved),
    draggedId: shownMoved?.id ?? null,
    /** The free slot being chosen, as it shows. */
    choosing: choosing && slotBetween(choosing.day, choosing.from, choosing.to),
    choosingDay: choosing?.day ?? null,
    unlessDraggedJustNow,
    grab,
    slotHandlers: (day: Date) => ({
      onPointerDown: startChoosing(day),
      onPointerMove: choose,
      onPointerUp: chosen,
      onPointerCancel: () => setChoosing(null),
    }),
  }
}

/** Where the exchange stands under the pointer: at another moment, or, stretched, for another duration. */
function landed(dragging: Dragging, event: globalThis.PointerEvent) {
  const spot = spotUnder(event.clientX, event.clientY)
  if (!spot) return {}
  const original = new Date(dragging.item.exchange.occurredAt)
  if (dragging.handling === 'stretch')
    return spot.minutes === null ? {} : { durationInMinutes: stretched(original, spot.minutes) }
  return { occurredAt: landing(original, spot, dragging.grabbedMinutes) }
}

function changed({ item, occurredAt, durationInMinutes }: Dragging): boolean {
  return (
    occurredAt.getTime() !== new Date(item.exchange.occurredAt).getTime() ||
    durationInMinutes !== (item.exchange.durationInMinutes ?? null)
  )
}
