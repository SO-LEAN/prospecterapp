import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import type { Model } from './model.ts'
import { type Note, wallOf } from './wall.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model

const wall = wallOf(BOOKING)

function noteOf(slug: string, kind?: Note['kind']): Note {
  const note = wall.notes.find(
    (candidate) => candidate.slug === slug && (kind === undefined || candidate.kind === kind),
  )
  if (note === undefined) throw new Error(`No post-it ${slug} on the wall`)
  return note
}

describe('the wall of a context', () => {
  it('gives each module a lane, a module that hears another one under it', () => {
    expect(wall.lanes.map((lane) => lane.label)).toEqual(['Booking page', 'Public link', 'Appointment'])
  })

  it('puts a root policy in the lane of the command it sends', () => {
    expect(noteOf('policy:ForgetAppointmentOfErasedExchangePolicy').step.lane).toBe('Appointment')
  })

  it('makes a step of the command, its aggregate and its event, which touch', () => {
    const open = noteOf('command:OpenBooking')
    const [aggregate, event] = (['aggregate', 'event'] as const).map((kind) =>
      open.step.notes.find((note) => note.kind === kind),
    )

    expect([
      aggregate?.label,
      aggregate?.via,
      event?.label,
      overlaps(open, aggregate),
      overlaps(aggregate, event),
    ]).toEqual(['Booking page', 'open', 'Booking opened', true, true])
  })

  it('tucks what a command asks under it, one under the other', () => {
    const open = noteOf('command:OpenBooking')
    const ports = open.step.notes.filter((note) => note.port)
    const stacked = ports
      .slice(1)
      .every((port, index) => port.y <= (ports[index]?.y ?? 0) + (ports[index]?.h ?? 0) + 2)

    expect([ports.map((port) => port.label), overlaps(open, ports[0]), stacked]).toEqual([
      ['Connected calendars', 'Hosts'],
      true,
      true,
    ])
  })

  it('draws an arrow only for a reaction', () => {
    expect(
      wall.edges.filter((edge) => edge.drawn).map((edge) => `${edge.from.label} → ${edge.to.label}`),
    ).toEqual([
      'Exchange facts changed → Forget appointment of erased exchange',
      'Forget appointment of erased exchange → Forget appointment of erased exchange',
      'Slot booked → Identify booker',
      'Slot booked → Publish slot booked',
    ])
  })

  it('reads a policy whenever the events it hears happen', () => {
    expect(noteOf('policy:ForgetAppointmentOfErasedExchangePolicy').whenever).toBe('Exchange facts changed')
  })

  it('stands a human actor once in a lane, left of the view it reads', () => {
    const members = wall.notes.filter((note) => note.slug === 'actor:ROLE_USER' && !note.badge)
    const view = noteOf('readModel:ConsultMyBookingPage')

    expect([
      members.length,
      members[0]?.step,
      (members[0]?.x ?? 0) + (members[0]?.w ?? 0) <= view.x + 1,
    ]).toEqual([1, view.step, true])
  })

  it('hangs from a view the commands its links offer, dashed when only in some states', () => {
    const offers = wall.brackets.find((bracket) => bracket.from === noteOf('readModel:ConsultMyBookingPage'))

    expect(
      offers?.items.filter((item) => !item.certain).map((item) => [item.command.label, item.tip]),
    ).toEqual([
      ['Change booking link', 'Offered only once the page has a link'],
      ['Close booking', 'Offered only while the page is open'],
      ['Open booking', 'Offered only while the page is closed'],
    ])
  })

  it("puts a badge of the member's AI on each command it can call", () => {
    const badged = wall.notes.filter((note) => note.badge).map((note) => note.step.main?.label)

    expect(badged).toContain('Open booking')
  })

  it('asks where a command is offered when no view of its actor offers it', () => {
    expect(noteOf('command:BookSlot').step.gap?.label).toBe('Offered where?')
  })

  it('says when a command records no event, and when nobody is seen reading a view', () => {
    const hotspots = wall.notes
      .filter((note) => note.kind === 'hotspot')
      .map((note) => `${note.step.main?.label}: ${note.label}`)

    expect(hotspots).toEqual([
      'Appointments: Who reads this?',
      'Forget appointment of erased exchange: No event recorded',
      'Book slot: Offered where?',
    ])
  })

  it('jumps from an event to the policy of this wall that hears it, and back', () => {
    const published = noteOf('policy:PublishSlotBookedHandler')
    const booked = wall.notes.find((note) => note.slug === 'event:SlotBooked' && note.jumps.length > 0)

    expect([
      booked?.jumps.map((jump) => jump.to),
      published.jumps.map((jump) => [jump.to, jump.back]),
    ]).toEqual([[published], [[booked, true]]])
  })

  it("lays another context's post-it on a piece of its paper", () => {
    const identify = noteOf('PersonalData/policy:IdentifyBookerPolicy')

    expect(identify.step.strips.find((strip) => strip.note === identify)?.context).toBe('Personal data')
  })
})

function overlaps(a: Note | undefined, b: Note | undefined): boolean {
  if (a === undefined || b === undefined) return false
  return a.x < b.x + b.w && b.x < a.x + a.w && a.y < b.y + b.h && b.y < a.y + a.h
}
