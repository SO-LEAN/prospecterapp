import { describe, expect, it } from 'vitest'
import { layOut } from './layout.ts'
import type { View, ViewNode } from './views.ts'

function node(key: string, type: ViewNode['type']): ViewNode {
  return { key, type, label: key, name: key, hover: key, detail: null, faded: false }
}

const chain: View = {
  id: 'overview',
  title: "Vue d'ensemble",
  nodes: [
    node('event:BookingOpened', 'event'),
    node('aggregate:BookingPage', 'aggregate'),
    node('command:OpenBooking', 'command'),
    node('actor:ROLE_USER', 'actor'),
  ],
  edges: [
    { from: 'actor:ROLE_USER', to: 'command:OpenBooking', certain: true },
    { from: 'command:OpenBooking', to: 'aggregate:BookingPage', certain: true },
    { from: 'aggregate:BookingPage', to: 'event:BookingOpened', certain: true },
  ],
}

describe('the layout of a view', () => {
  it('reads a chain from left to right', async () => {
    const laidOut = await layOut(chain)

    const fromLeftToRight = [...laidOut.nodes]
      .sort((one, other) => one.x - other.x)
      .map((placed) => placed.node.key)
    expect(fromLeftToRight).toEqual([
      'actor:ROLE_USER',
      'command:OpenBooking',
      'aggregate:BookingPage',
      'event:BookingOpened',
    ])
  })

  it('puts no post-it over another', async () => {
    const crowded: View = {
      ...chain,
      nodes: [...chain.nodes, node('event:BookingClosed', 'event'), node('module:PublicLink', 'module')],
      edges: [...chain.edges, { from: 'aggregate:BookingPage', to: 'event:BookingClosed', certain: false }],
    }

    const { nodes } = await layOut(crowded)

    const overlapping = nodes.flatMap((one, index) =>
      nodes
        .slice(index + 1)
        .filter(
          (other) =>
            one.x < other.x + other.width &&
            other.x < one.x + one.width &&
            one.y < other.y + other.height &&
            other.y < one.y + one.height,
        )
        .map((other) => [one.node.key, other.node.key]),
    )
    expect(overlapping).toEqual([])
  })
})
