import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import type { Model } from './model.ts'
import { type Note, wallOf } from './wall.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model

const wall = wallOf(BOOKING)

/** A model of the code as the generator last read it, by the folder of its context. */
function modelOf(folder: string): Model {
  return JSON.parse(readFileSync(`public/docs/event-storming/${folder}/model.json`, 'utf8')) as Model
}

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

  it('gives a context without modules one lane, named after it', () => {
    expect(wallOf(modelOf('reporting')).lanes.map((lane) => lane.label)).toEqual(['Reporting'])
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

  it('says when a command records no event, and when no view of its actor offers it', () => {
    const hotspots = wall.notes
      .filter((note) => note.kind === 'hotspot')
      .map((note) => `${note.step.main?.label}: ${note.label}`)

    expect(hotspots).toEqual([
      'Forget appointment of erased exchange: No event recorded',
      'Book slot: Offered where?',
    ])
  })

  it('shows the aggregate a command changes without an event, then says no event is recorded', () => {
    const campaigns = wallOf(modelOf('campaigns'))
    const reopen = campaigns.notes.find(
      (note) => note.slug === 'command:ReopenRecipientOfErasedExchange' && note.kind === 'command',
    )
    const changed = reopen?.step.notes.filter((note) => note.kind === 'aggregate') ?? []
    const gaps = reopen?.step.notes.filter((note) => note.kind === 'hotspot') ?? []
    const facing = changed.map((aggregate) =>
      gaps.find(
        (gap) => gap.y < aggregate.y + aggregate.h && aggregate.y < gap.y + gap.h && gap.x > aggregate.x,
      ),
    )

    expect([
      changed.map((aggregate) => `${aggregate.label} · ${aggregate.via}()`),
      facing.map((gap) => gap?.label),
      gaps[0]?.why,
      changed.every((aggregate, index) => !overlaps(aggregate, changed[index + 1])),
    ]).toEqual([
      ['Campaign · forgetExchangeGoneWithItsOrganization()', 'Campaign · reopenAfterErasureOf()'],
      ['No event recorded', 'No event recorded'],
      'This command changes “Campaign” through forgetExchangeGoneWithItsOrganization(), and that change records no event: nothing on the wall says what happened. Either an event is missing, or the generator cannot see it.',
      true,
    ])
  })

  it('keeps the hotspot alone after a command that changes no aggregate', () => {
    const erase = wallOf(modelOf('campaigns')).notes.find(
      (note) => note.slug === 'command:EraseCampaign' && note.kind === 'command',
    )

    expect(
      erase?.step.notes
        .filter((note) => !note.port && note !== erase && note.kind !== 'actor')
        .map((note) => [note.kind, note.why]),
    ).toEqual([
      [
        'hotspot',
        'This command changes no aggregate that records an event: nothing on the wall says what happened. Either an event is missing, or the generator cannot see it.',
      ],
    ])
  })

  it('leads a projection to the view it keeps, touching it, instead of saying it records no event', () => {
    const forget =
      'command:Prospecter\\Core\\Booking\\Application\\Appointment\\ForgetAppointmentOfErasedExchange\\ForgetAppointmentOfErasedExchange'
    const page =
      'readModel:Prospecter\\Core\\Booking\\Application\\BookingPage\\ConsultMyBookingPage\\ConsultMyBookingPage'
    const projected = wallOf({
      ...BOOKING,
      links: [...BOOKING.links, { from: forget, to: page, via: null, certain: true, when: null }],
    })
    const command = projected.notes.find((note) => note.slug === 'command:ForgetAppointmentOfErasedExchange')
    const kept = command?.step.notes.find((note) => note.kind === 'readModel')

    expect([
      kept?.label,
      overlaps(command, kept),
      (kept?.x ?? 0) + (kept?.w ?? 0) <= (command?.step.width ?? 0),
      command?.step.notes.filter((note) => note.kind === 'hotspot').map((note) => note.label),
    ]).toEqual(['My booking page', true, true, []])
  })

  it('tucks what a projection asks under the views it keeps', () => {
    const open = 'command:Prospecter\\Core\\Booking\\Application\\BookingPage\\OpenBooking\\OpenBooking'
    const views = BOOKING.stickies.filter(
      (sticky) => sticky.type === 'readModel' && sticky.context === 'Booking',
    )
    const projected = wallOf({
      ...BOOKING,
      links: [
        ...BOOKING.links,
        ...views.map((view) => ({ from: open, to: view.id, via: null, certain: true, when: null })),
      ],
    })
    const command = projected.notes.find((note) => note.slug === 'command:OpenBooking')
    const kept = command?.step.notes.filter((note) => note.kind === 'readModel') ?? []
    const ports = command?.step.notes.filter((note) => note.port) ?? []
    const keptBottom = Math.max(...kept.map((view) => view.y + view.h))

    expect([kept.length > 1, ports.length > 0, ports.every((port) => port.y >= keptBottom)]).toEqual([
      true,
      true,
      true,
    ])
  })

  it.each([
    ['operator', 'Platform operator'],
    ['schedule', 'Daily schedule'],
  ])('never asks where a command the %s sends is offered: it looks at no screen', (name, label) => {
    const windows =
      'command:Prospecter\\Core\\Booking\\Application\\BookingPage\\SetWeeklyWindows\\SetWeeklyWindows'
    const page =
      'readModel:Prospecter\\Core\\Booking\\Application\\BookingPage\\ConsultMyBookingPage\\ConsultMyBookingPage'
    const actor = `actor:${name}`
    const unoffered = wallOf({
      ...BOOKING,
      stickies: [
        ...BOOKING.stickies,
        {
          ...BOOKING.stickies.find((sticky) => sticky.id === 'actor:ROLE_USER'),
          id: actor,
          slug: actor,
          name,
          label,
        } as Model['stickies'][number],
      ],
      links: [
        ...BOOKING.links.filter((link) => link.to !== windows),
        { from: actor, to: windows, via: null, certain: true, when: null },
        { from: actor, to: page, via: null, certain: true, when: null },
      ],
    })
    const command = unoffered.notes.find((note) => note.slug === 'command:SetWeeklyWindows')

    expect([command?.step.senders.map((sender) => sender.label), command?.step.gap]).toEqual([[label], null])
  })

  it('says nobody sends a command that no actor and no policy sends', () => {
    const close = 'command:Prospecter\\Core\\Booking\\Application\\BookingPage\\CloseBooking\\CloseBooking'
    const unsent = wallOf({
      ...BOOKING,
      links: BOOKING.links.filter((link) => !(link.to === close && link.from.startsWith('actor:'))),
    })
    const command = unsent.notes.find((note) => note.slug === 'command:CloseBooking')

    expect(command?.step.notes.filter((note) => note.kind === 'hotspot').map((note) => note.label)).toEqual([
      'Nobody sends this',
    ])
  })

  it('says nobody calls a read model that no actor reads and nothing takes', () => {
    const unread = wallOf({
      ...BOOKING,
      stickies: BOOKING.stickies.map((sticky) => ({ ...sticky, readBy: [] })),
    })
    const appointments = unread.notes.find((note) => note.slug === 'readModel:ConsultAppointments')

    expect(
      appointments?.step.notes.filter((note) => note.kind === 'hotspot').map((note) => note.label),
    ).toEqual(['Nobody calls this'])
  })

  it('jumps from an event to the policy of this wall that hears it, and back', () => {
    const published = noteOf('policy:PublishSlotBookedHandler')
    const booked = wall.notes.find((note) => note.slug === 'event:SlotBooked' && note.jumps.length > 0)

    expect([
      booked?.jumps.map((jump) => jump.to),
      published.jumps.map((jump) => [jump.to, jump.back]),
    ]).toEqual([[published], [[booked, true]]])
  })

  it('names on a pictogram the other contexts that answer a port, when no published service is seen answering', () => {
    const unanswered = wallOf({
      ...BOOKING,
      stickies: BOOKING.stickies.map((sticky) => ({ ...sticky, answers: [] })),
    })
    const hosts = unanswered.notes.find((note) => note.slug === 'externalSystem:Hosts')

    expect(hosts?.jumps.map((jump) => [jump.href, jump.tip])).toEqual([
      [null, 'Answered by Accounts, Personal data'],
    ])
  })

  it('jumps from a port to the published services that answer it, on their wall', () => {
    const hosts = wall.notes.find((note) => note.slug === 'externalSystem:Hosts')

    expect(hosts?.jumps.map((jump) => [jump.href, jump.tip])).toEqual([
      ['../accounts/#at=readModel%3AConsultMembers', 'Answered by “Members” · Accounts'],
      ['../personal-data/#at=readModel%3APresentMembers', 'Answered by “Present members” · Personal data'],
    ])
  })

  it('points a jump to another wall away, never back', () => {
    expect(
      wall.notes
        .flatMap((note) => note.jumps)
        .filter((jump) => jump.href !== null)
        .every((jump) => !jump.back),
    ).toBe(true)
  })

  it('jumps from a published service to the ports that read it, on their wall', () => {
    expect(noteOf('readModel:ConsultAppointments').jumps.map((jump) => jump.href)).toEqual([
      '../personal-data/#at=externalSystem%3ABookers',
      '../prospecting/#at=externalSystem%3ABookedAppointments',
    ])
  })

  it('jumps from a published event to the policies of the other contexts that hear it, on their wall', () => {
    const published = wall.notes.find((note) => note.slug === 'published:SlotBooked')

    expect(published?.jumps.map((jump) => [jump.href, jump.tip])).toEqual([
      ['../personal-data/#at=policy%3AIdentifyBookerPolicy', 'Heard by “Identify booker” · Personal data'],
    ])
  })

  it('jumps from the event of another context a policy hears to that event, on its wall', () => {
    expect(noteOf('Prospecting/published:ExchangeFactsChanged').jumps.map((jump) => jump.href)).toEqual([
      '../prospecting/#at=published%3AExchangeFactsChanged',
    ])
  })

  it('says on the card of a hotspot of what nothing reaches that it is a suspicion to check', () => {
    const unsent = wallOf({
      ...BOOKING,
      links: BOOKING.links.filter(
        (link) => !(link.from.startsWith('actor:') && link.to.endsWith('\\CloseBooking')),
      ),
      stickies: BOOKING.stickies.map((sticky) => ({ ...sticky, readBy: [] })),
    })
    const whys = unsent.notes
      .filter((note) => note.label === 'Nobody sends this' || note.label === 'Nobody calls this')
      .map((note) => note.why)

    expect(whys).toEqual([
      'No controller, no tool of the MCP server and no class of another context reads this view. It is a suspicion to check: the analysis follows the calls it reads, and a dynamic call escapes it.',
      'No controller, no command of the console, no daily task, no tool of the MCP server and no policy sends this command. It is a suspicion to check: the analysis follows the calls it reads, and a dynamic call escapes it.',
    ])
  })

  it('touches the command nobody sends with its hotspot, without an arrow', () => {
    const unsent = wallOf({
      ...BOOKING,
      links: BOOKING.links.filter(
        (link) => !(link.from.startsWith('actor:') && link.to.endsWith('\\CloseBooking')),
      ),
    })
    const edge = unsent.edges.find((candidate) => candidate.from.label === 'Nobody sends this')

    expect([edge?.to.label, edge?.certain, edge?.drawn]).toEqual(['Close booking', true, false])
  })

  it('names the wall a published service is read from, and the port that reads it', () => {
    expect(noteOf('readModel:ConsultAppointments').jumps.map((jump) => jump.tip)).toEqual([
      'Read by “Bookers” · Personal data',
      'Read by “Booked appointments” · Prospecting',
    ])
  })

  it('jumps from a published service to no wall for a reader of no context', () => {
    const appointments =
      'readModel:Prospecter\\Core\\Booking\\Contract\\Service\\ConsultAppointments\\ConsultAppointments'
    const readByTheDelivery = wallOf({
      ...BOOKING,
      stickies: BOOKING.stickies.map((sticky) =>
        sticky.id === appointments
          ? { ...sticky, readBy: [{ context: null, slug: null, label: 'AgendaReader' }] }
          : sticky,
      ),
    })

    expect(readByTheDelivery.notes.find((note) => note.sticky?.id === appointments)?.jumps).toEqual([])
  })

  it('names the wall an event a policy hears comes from', () => {
    expect(noteOf('Prospecting/published:ExchangeFactsChanged').jumps.map((jump) => jump.tip)).toEqual([
      'Heard from “Exchange facts changed” · Prospecting',
    ])
  })

  it('puts no pictogram on a port that no other context answers', () => {
    const unanswered = wallOf({
      ...BOOKING,
      stickies: BOOKING.stickies.map((sticky) => ({ ...sticky, answers: [], answeredBy: [] })),
    })

    expect(unanswered.notes.filter((note) => note.port).flatMap((note) => note.jumps)).toEqual([])
  })

  it('puts a use case of no module of a context with modules in a lane of the application', () => {
    const rootless = wallOf({
      ...BOOKING,
      stickies: BOOKING.stickies.map((sticky) =>
        sticky.slug === 'command:CloseBooking' ? { ...sticky, module: null } : sticky,
      ),
    })

    expect(rootless.lanes.map((lane) => lane.name)).toContain('Application')
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
