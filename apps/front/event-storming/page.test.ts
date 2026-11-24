import { describe, expect, it } from 'vitest'
import type { LaidOutView } from './layout.ts'
import { pageOf } from './page.ts'

const openBooking = {
  key: 'command:OpenBooking',
  type: 'command',
  label: 'Activer et couper',
  name: 'OpenBooking',
  hover: 'Core\\OpenBooking',
  detail: null,
  faded: false,
} as const
const bookingPage = {
  key: 'aggregate:BookingPage@command:OpenBooking',
  type: 'aggregate',
  label: 'BookingPage',
  name: 'BookingPage',
  hover: 'Core\\BookingPage',
  detail: null,
  faded: true,
} as const

const overview: LaidOutView = {
  view: { id: 'overview', title: "Vue d'ensemble", nodes: [openBooking, bookingPage], edges: [] },
  width: 600,
  height: 200,
  nodes: [
    { node: openBooking, x: 24, y: 24, width: 176, height: 104 },
    { node: bookingPage, x: 264, y: 24, width: 176, height: 104 },
  ],
  edges: [
    {
      edge: { from: openBooking.key, to: bookingPage.key, certain: false },
      points: [
        { x: 200, y: 76 },
        { x: 264, y: 76 },
      ],
    },
  ],
}

function pageIn(html: string): Document {
  return new DOMParser().parseFromString(html, 'text/html')
}

describe('the page of an event storming', () => {
  it('shows a use case on a post-it of its colour', () => {
    const sticky = pageIn(pageOf('Booking', [overview])).querySelector('[data-key="command:OpenBooking"]')

    expect(sticky?.getAttribute('class')).toBe('sticky type-command')
  })

  it('names the class of a post-it on hover', () => {
    const sticky = pageIn(pageOf('Booking', [overview])).querySelector('[data-key="command:OpenBooking"]')

    expect(sticky?.querySelector('title')?.textContent).toBe('Core\\OpenBooking')
  })

  it('places a post-it to a tenth of a point, so that the page reads the same from one generation to the next', () => {
    const placed = { ...overview, nodes: [{ ...overview.nodes[0], x: 24.333333333333332 }] } as LaidOutView

    const sticky = pageIn(pageOf('Booking', [placed])).querySelector('[data-key="command:OpenBooking"]')

    expect(sticky?.getAttribute('transform')).toBe('translate(24.3,24)')
  })

  it('fades a post-it of another module or context', () => {
    const sticky = pageIn(pageOf('Booking', [overview])).querySelector(`[data-key="${bookingPage.key}"]`)

    expect(sticky?.classList.contains('faded')).toBe(true)
  })

  it('dashes an arrow that only happens under a condition', () => {
    const arrow = pageIn(pageOf('Booking', [overview])).querySelector('.link')

    expect(arrow?.getAttribute('class')).toBe('link uncertain')
  })

  it('cuts a label into lines of its words', () => {
    const labelled = {
      ...overview,
      nodes: [
        {
          ...overview.nodes[0],
          node: { ...openBooking, label: 'Activer et couper ma prise de rendez-vous' },
        },
      ],
    } as LaidOutView

    const lines = [...pageIn(pageOf('Booking', [labelled])).querySelectorAll('.sticky .label')].map(
      (line) => line.textContent,
    )

    expect(lines).toEqual(['Activer et couper ma', 'prise de rendez-vous'])
  })

  it('cuts a name too long for a line between the parts of the name', () => {
    const named = {
      ...overview,
      nodes: [{ ...overview.nodes[0], node: { ...openBooking, label: 'InactivePeriodRemoved' } }],
    } as LaidOutView

    const lines = [...pageIn(pageOf('Booking', [named])).querySelectorAll('.sticky .label')].map(
      (line) => line.textContent,
    )

    expect(lines).toEqual(['InactivePeriod', 'Removed'])
  })

  it('writes under a label that is a name what the post-it says of itself', () => {
    const published = {
      ...openBooking,
      type: 'event',
      label: 'SlotBooked',
      name: 'SlotBooked',
      detail: 'Événement publié',
    } as const
    const saying = { ...overview, nodes: [{ ...overview.nodes[0], node: published }] } as LaidOutView

    const caption = pageIn(pageOf('Booking', [saying])).querySelector('.sticky .caption')

    expect(caption?.textContent).toBe('Événement publié')
  })

  it('offers a filter for each type of post-it', () => {
    const filters = [...pageIn(pageOf('Booking', [overview])).querySelectorAll('fieldset input')].map(
      (input) => input.getAttribute('data-kind'),
    )

    expect(filters).toEqual([
      'actor',
      'command',
      'aggregate',
      'event',
      'policy',
      'readModel',
      'externalSystem',
      'module',
    ])
  })
})
