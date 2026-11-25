import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import { gridOf } from './grid.ts'
import type { Model } from './model.ts'
import { type Note, wallOf } from './wall.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model

const wall = wallOf(BOOKING)
const grid = gridOf(wall, 'roomy')

function noteOf(slug: string): Note {
  const note = wall.notes.find((candidate) => candidate.slug === slug && !candidate.badge)
  if (note === undefined) throw new Error(`No post-it ${slug} on the wall`)
  return note
}

function placeOf(slug: string): { x: number; y: number } {
  const place = grid.places.get(noteOf(slug))
  if (place === undefined) throw new Error(`${slug} has no place`)
  return place
}

describe('the grid of a wall', () => {
  it('stacks the independent use cases of a lane one under the other, at its left', () => {
    const [windows, channels] = ['command:SetWeeklyWindows', 'command:OfferChannels'].map(placeOf)

    expect([windows?.x, (windows?.y ?? 0) > (channels?.y ?? 0)]).toEqual([channels?.x, true])
  })

  it('moves a step to the right only when a reaction starts it, right of the event it hears', () => {
    const booked = wall.notes.find((note) => note.slug === 'event:SlotBooked' && note.jumps.length > 0)
    const publish = placeOf('policy:PublishSlotBookedHandler')

    expect(publish.x).toBeGreaterThan((booked === undefined ? 0 : (grid.places.get(booked)?.x ?? 0)) + 132)
  })

  it('gives a lane the width of its rightmost post-it and the margin of the wall', () => {
    const lane = wall.lanes.find((candidate) => candidate.name === 'PublicLink')
    const rights = wall.notes
      .filter((note) => note.step.lane === 'PublicLink')
      .map((note) => (grid.places.get(note)?.x ?? 0) + note.w)

    expect(lane === undefined ? undefined : grid.lanes.get(lane)?.width).toBe(Math.max(...rights) + 150)
  })

  it("orders an actor's commands by aggregate, then by name", () => {
    const commands = wall.lanes[0]?.groups[0]?.commands ?? []
    const byHeight = [...commands].sort(
      (a, b) => (grid.places.get(a.main as Note)?.y ?? 0) - (grid.places.get(b.main as Note)?.y ?? 0),
    )

    expect(byHeight.map((step) => step.main?.label)).toEqual([
      'Add inactive period',
      'Change booking link',
      'Close booking',
      'Offer channels',
      'Open booking',
      'Remove inactive period',
      'Set weekly windows',
    ])
  })

  it('puts each lane under the one before', () => {
    const lanes = wall.lanes.map((lane) => grid.lanes.get(lane))

    expect(
      lanes.every(
        (lane, index) =>
          index === 0 || (lane?.top ?? 0) === (lanes[index - 1]?.top ?? 0) + (lanes[index - 1]?.height ?? 0),
      ),
    ).toBe(true)
  })

  it('turns what a use case asks into small squares in compact rows, and the wall gets shorter', () => {
    const compact = gridOf(wall, 'compact')
    const ports = wall.notes.filter((note) => note.port).map((note) => compact.places.get(note))

    expect([ports.every((place) => place?.w === 25 && place.h === 25), compact.height < grid.height]).toEqual(
      [true, true],
    )
  })

  it('names the steps of a folded lane, one name after the other', () => {
    const [bookingPage] = wall.lanes
    const labels = bookingPage === undefined ? [] : (grid.lanes.get(bookingPage)?.labels ?? [])
    const apart = labels.every((label, index) => {
      const before = labels[index - 1]
      return before === undefined || before.y !== label.y || before.x + before.text.length * 7.4 <= label.x
    })

    expect([labels.map((label) => label.text).slice(0, 2), apart]).toEqual([
      ['My booking page', 'Add inactive period'],
      true,
    ])
  })

  it('lays the same wall out the same way at each generation', () => {
    expect(gridOf(wallOf(BOOKING), 'roomy')).toEqual(gridOf(wallOf(BOOKING), 'roomy'))
  })

  it('leans a post-it by its own name, whatever else stands on the wall', () => {
    const added = BOOKING.stickies.find((sticky) => sticky.slug === 'command:AddInactivePeriod')?.id
    const fewer: Model = {
      ...BOOKING,
      stickies: BOOKING.stickies.filter((sticky) => sticky.id !== added),
      links: BOOKING.links.filter((link) => link.from !== added && link.to !== added),
    }
    const open = (model: Model): Note | undefined =>
      wallOf(model).notes.find((note) => note.slug === 'command:OpenBooking')

    expect(open(fewer)?.tilt).toBe(open(BOOKING)?.tilt)
  })
})
