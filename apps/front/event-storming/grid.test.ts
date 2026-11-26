import { readdirSync, readFileSync } from 'node:fs'
import { beforeEach, describe, expect, it } from 'vitest'
import { type Grid, gridOf } from './grid.ts'
import type { Model } from './model.ts'
import { type Note, type Step, type Wall, wallOf } from './wall.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model

/** Built again in each test, so that the mutation knows which tests run each line of the generator. */
let wall: Wall
let grid: Grid

beforeEach(() => {
  wall = wallOf(BOOKING)
  grid = gridOf(wall, 'roomy')
})

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

  it('orders the commands a view offers by aggregate, then by name', () => {
    const commands = wall.lanes[0]?.blocks[0]?.commands ?? []
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

  for (const folder of readdirSync('public/docs/event-storming', { withFileTypes: true }).filter((entry) =>
    entry.isDirectory(),
  )) {
    it(`puts each command a view offers beside it or under it, so that its line never climbs, on ${folder.name}`, () => {
      const other = wallOf(
        JSON.parse(readFileSync(`public/docs/event-storming/${folder.name}/model.json`, 'utf8')) as Model,
      )
      const laid = gridOf(other, 'roomy')
      const climbing = other.brackets.flatMap((bracket) =>
        bracket.items
          .filter(
            (item) => (laid.places.get(item.command)?.y ?? 0) < (laid.places.get(bracket.from)?.y ?? 0) - 10,
          )
          .map((item) => `${bracket.from.label} → ${item.command.label}`),
      )

      expect(climbing).toEqual([])
    })
  }

  it('stands the commands of a policy that sends several right of it, one under the other, each with its arrow', () => {
    const reporting = wallOf(
      JSON.parse(readFileSync('public/docs/event-storming/reporting/model.json', 'utf8')) as Model,
    )
    const laid = gridOf(reporting, 'roomy')
    const policy = reporting.notes.find((note) => note.kind === 'policy' && note.label === 'Record facts')
    const sent = reporting.edges.filter((edge) => edge.drawn && edge.from === policy).map((edge) => edge.to)
    const at = sent.map((command) => laid.places.get(command))
    const right = (laid.places.get(policy as Note)?.x ?? 0) + (policy?.w ?? 0)

    expect([
      sent.map((command) => command.label),
      at.every((place) => Math.abs((place?.x ?? 0) - (at[0]?.x ?? 0)) < 1 && (place?.x ?? 0) > right),
      at.every((place, index) => index === 0 || (place?.y ?? 0) > (at[index - 1]?.y ?? 0)),
    ]).toEqual([['Record contact facts', 'Record exchange facts', 'Record organization facts'], true, true])
  })

  it.each([
    ['Exchange erased', 'Discard attachments of erased exchange'],
    ['Duplicate suspected', 'Suspect duplicate in return'],
  ])('stands a reaction right of the event it hears, in its row or lower: %s, then %s', (heard, reaction) => {
    const prospecting = wallOf(
      JSON.parse(readFileSync('public/docs/event-storming/prospecting/model.json', 'utf8')) as Model,
    )
    const laid = gridOf(prospecting, 'roomy')
    const edge = prospecting.edges.find(
      (candidate) => candidate.drawn && candidate.from.label === heard && candidate.to.label === reaction,
    )
    const from = edge === undefined ? undefined : laid.places.get(edge.from)
    const to = edge === undefined ? undefined : laid.places.get(edge.to)

    expect([
      (to?.x ?? 0) >= (from?.x ?? 0) + (from?.w ?? 0),
      (laid.rows.get(edge?.to.step as Step) ?? -1) >= (laid.rows.get(edge?.from.step as Step) ?? 0),
    ]).toEqual([true, true])
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
