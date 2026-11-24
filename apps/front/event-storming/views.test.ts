import { describe, expect, it } from 'vitest'
import type { Link, Model, Sticky, StickyType } from './model.ts'
import { viewsOf } from './views.ts'

function sticky(type: StickyType, name: string, module: string | null = null, context = 'Booking'): Sticky {
  return {
    id: `${type}:${name}`,
    type,
    label: name,
    name,
    class: `Core\\${name}`,
    context,
    module,
    detail: null,
  }
}

function link(from: Sticky, to: Sticky, via: string | null = null): Link {
  return { from: from.id, to: to.id, via, certain: true }
}

const openBooking = sticky('command', 'OpenBooking', 'BookingPage')
const bookingPage = sticky('aggregate', 'BookingPage')
const bookingOpened = sticky('event', 'BookingOpened')
const bookSlot = sticky('command', 'BookSlot', 'PublicLink')
const slotBooked = sticky('event', 'SlotBooked')

describe('the views of an event storming', () => {
  it('shows a context without modules in its overview alone', () => {
    const model: Model = {
      context: 'Booking',
      modules: [],
      stickies: [{ ...openBooking, module: null }, bookingPage, bookingOpened],
      links: [link(openBooking, bookingPage, 'open'), link(bookingPage, bookingOpened, 'open')],
    }

    const views = viewsOf(model)

    expect(views.map((view) => [view.id, view.nodes.map((node) => node.key)])).toEqual([
      [
        'overview',
        ['command:OpenBooking', 'aggregate:BookingPage@command:OpenBooking', 'event:BookingOpened'],
      ],
    ])
  })

  it('gives each module a view of its own use cases', () => {
    const model: Model = {
      context: 'Booking',
      modules: ['BookingPage', 'PublicLink'],
      stickies: [openBooking, bookingPage, bookingOpened, bookSlot, slotBooked],
      links: [
        link(openBooking, bookingPage, 'open'),
        link(bookingPage, bookingOpened, 'open'),
        link(bookSlot, bookingPage, 'book'),
        link(bookingPage, slotBooked, 'book'),
      ],
    }

    const views = viewsOf(model)

    expect(views.slice(1).map((view) => [view.id, view.nodes.map((node) => node.key)])).toEqual([
      [
        'module:BookingPage',
        ['command:OpenBooking', 'aggregate:BookingPage@command:OpenBooking', 'event:BookingOpened'],
      ],
      [
        'module:PublicLink',
        ['command:BookSlot', 'aggregate:BookingPage@command:BookSlot', 'event:SlotBooked'],
      ],
    ])
  })

  it('shows faded, at the edge of a module, the event of another module its reaction hears', () => {
    const publish = sticky('policy', 'PublishSlotBooked', 'Appointment')
    const published = sticky('event', 'PublishedSlotBooked')
    const model: Model = {
      context: 'Booking',
      modules: ['Appointment', 'PublicLink'],
      stickies: [bookSlot, bookingPage, slotBooked, publish, published],
      links: [
        link(bookSlot, bookingPage, 'book'),
        link(bookingPage, slotBooked, 'book'),
        link(slotBooked, publish),
        link(publish, published),
      ],
    }

    const appointment = viewsOf(model).find((view) => view.id === 'module:Appointment')

    expect(appointment?.nodes.map((node) => [node.key, node.faded])).toEqual([
      ['event:SlotBooked', true],
      ['policy:PublishSlotBooked', false],
      ['event:PublishedSlotBooked', false],
    ])
  })

  it('shows faded, before a module command, the policy that sends it from elsewhere and what that policy hears', () => {
    const erased = sticky('event', 'ExchangeFactsChanged', null, 'Prospecting')
    const policy = sticky('policy', 'ForgetAppointmentPolicy')
    const forget = sticky('command', 'ForgetAppointment', 'Appointment')
    const model: Model = {
      context: 'Booking',
      modules: ['Appointment'],
      stickies: [erased, policy, forget],
      links: [link(erased, policy), { ...link(policy, forget), certain: false }],
    }

    const appointment = viewsOf(model).find((view) => view.id === 'module:Appointment')

    expect([appointment?.nodes.map((node) => [node.key, node.faded]), appointment?.edges]).toEqual([
      [
        ['event:ExchangeFactsChanged', true],
        ['policy:ForgetAppointmentPolicy', true],
        ['command:ForgetAppointment', false],
      ],
      [
        { from: 'event:ExchangeFactsChanged', to: 'policy:ForgetAppointmentPolicy', certain: true },
        { from: 'policy:ForgetAppointmentPolicy', to: 'command:ForgetAppointment', certain: false },
      ],
    ])
  })

  it('shows faded, after an event of the view, the reactions of other modules or contexts that hear it', () => {
    const identify = sticky('policy', 'IdentifyBookerPolicy', null, 'PersonalData')
    const model: Model = {
      context: 'Booking',
      modules: ['PublicLink'],
      stickies: [bookSlot, bookingPage, slotBooked, identify],
      links: [
        link(bookSlot, bookingPage, 'book'),
        link(bookingPage, slotBooked, 'book'),
        link(slotBooked, identify),
      ],
    }

    const publicLink = viewsOf(model).find((view) => view.id === 'module:PublicLink')

    expect([publicLink?.nodes.at(-1), publicLink?.edges.at(-1)]).toEqual([
      {
        key: 'policy:IdentifyBookerPolicy',
        type: 'policy',
        label: 'IdentifyBookerPolicy',
        name: 'IdentifyBookerPolicy',
        hover: 'Core\\IdentifyBookerPolicy',
        detail: 'PersonalData',
        faded: true,
      },
      { from: 'event:SlotBooked', to: 'policy:IdentifyBookerPolicy', certain: true },
    ])
  })

  it('shows in the overview of a context the modules, the events from one to another and the use cases at its root', () => {
    const publish = sticky('policy', 'PublishSlotBooked', 'Appointment')
    const erased = sticky('event', 'ExchangeFactsChanged', null, 'Prospecting')
    const policy = sticky('policy', 'ForgetAppointmentPolicy')
    const forget = sticky('command', 'ForgetAppointment', 'Appointment')
    const model: Model = {
      context: 'Booking',
      modules: ['Appointment', 'PublicLink'],
      stickies: [bookSlot, bookingPage, slotBooked, publish, erased, policy, forget],
      links: [
        link(bookSlot, bookingPage, 'book'),
        link(bookingPage, slotBooked, 'book'),
        link(slotBooked, publish),
        link(erased, policy),
        { ...link(policy, forget), certain: false },
      ],
    }

    const [overview] = viewsOf(model)

    expect([overview?.nodes.map((node) => [node.key, node.type, node.faded]), overview?.edges]).toEqual([
      [
        ['module:Appointment', 'module', false],
        ['module:PublicLink', 'module', false],
        ['event:SlotBooked', 'event', false],
        ['event:ExchangeFactsChanged', 'event', true],
        ['policy:ForgetAppointmentPolicy', 'policy', false],
      ],
      [
        { from: 'module:PublicLink', to: 'event:SlotBooked', certain: true },
        { from: 'event:SlotBooked', to: 'module:Appointment', certain: true },
        { from: 'event:ExchangeFactsChanged', to: 'policy:ForgetAppointmentPolicy', certain: true },
        { from: 'policy:ForgetAppointmentPolicy', to: 'module:Appointment', certain: false },
      ],
    ])
  })

  it('names the context a post-it of another context comes from', () => {
    const erased = {
      ...sticky('event', 'ExchangeFactsChanged', null, 'Prospecting'),
      detail: 'Événement publié',
    }
    const policy = sticky('policy', 'ForgetAppointmentPolicy')
    const model: Model = {
      context: 'Booking',
      modules: [],
      stickies: [erased, policy],
      links: [link(erased, policy)],
    }

    const [overview] = viewsOf(model)

    expect(overview?.nodes[0]?.detail).toBe('Prospecting · Événement publié')
  })

  it('puts the actor before a use case', () => {
    const member = sticky('actor', 'ROLE_USER')
    const model: Model = {
      context: 'Booking',
      modules: [],
      stickies: [{ ...openBooking, module: null }, member],
      links: [link(member, openBooking)],
    }

    const [overview] = viewsOf(model)

    expect(overview?.edges).toEqual([{ from: 'actor:ROLE_USER', to: 'command:OpenBooking', certain: true }])
  })

  it('puts the external systems a use case asks after it', () => {
    const calendars = sticky('externalSystem', 'ConnectedCalendars')
    const model: Model = {
      context: 'Booking',
      modules: [],
      stickies: [{ ...openBooking, module: null }, calendars],
      links: [link(openBooking, calendars)],
    }

    const [overview] = viewsOf(model)

    expect(overview?.edges).toEqual([
      { from: 'command:OpenBooking', to: 'externalSystem:ConnectedCalendars', certain: true },
    ])
  })
})
