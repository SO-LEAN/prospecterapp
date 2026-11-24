import ELK, { type ElkExtendedEdge, type ElkNode } from 'elkjs/lib/elk.bundled.js'
import type { View, ViewEdge, ViewNode } from './views.ts'

export interface Point {
  x: number
  y: number
}

export interface PlacedNode {
  node: ViewNode
  x: number
  y: number
  width: number
  height: number
}

export interface RoutedEdge {
  edge: ViewEdge
  points: Point[]
}

export interface LaidOutView {
  view: View
  width: number
  height: number
  nodes: PlacedNode[]
  edges: RoutedEdge[]
}

export const STICKY = { width: 176, height: 104 }
export const MODULE = { width: 240, height: 136 }

/**
 * ELK's layered algorithm puts each post-it one layer after the one its arrow comes from, so that the chains read
 * from left to right, and routes the arrows between the layers without crossing a post-it.
 */
const LAYERED_FROM_LEFT_TO_RIGHT = {
  'elk.algorithm': 'layered',
  'elk.direction': 'RIGHT',
  'elk.edgeRouting': 'ORTHOGONAL',
  'elk.layered.considerModelOrder.strategy': 'NODES_AND_EDGES',
  'elk.spacing.nodeNode': '28',
  'elk.layered.spacing.nodeNodeBetweenLayers': '64',
  'elk.padding': '[top=24,left=24,bottom=24,right=24]',
}

export async function layOut(view: View): Promise<LaidOutView> {
  const graph: ElkNode = {
    id: view.id,
    layoutOptions: LAYERED_FROM_LEFT_TO_RIGHT,
    children: view.nodes.map((node) => ({ id: node.key, ...(node.type === 'module' ? MODULE : STICKY) })),
    edges: view.edges.map(
      (edge, index): ElkExtendedEdge => ({ id: `edge-${index}`, sources: [edge.from], targets: [edge.to] }),
    ),
  }
  const laidOut = await new ELK().layout(graph)
  return {
    view,
    width: laidOut.width ?? 0,
    height: laidOut.height ?? 0,
    nodes: (laidOut.children ?? []).map((child, index) => placed(view.nodes[index], child)),
    edges: (laidOut.edges ?? []).map((edge, index) => routed(view.edges[index], edge)),
  }
}

function placed(node: ViewNode | undefined, child: ElkNode): PlacedNode {
  if (node === undefined) throw new Error(`ELK placed ${child.id}, which the view does not hold`)
  return { node, x: child.x ?? 0, y: child.y ?? 0, width: child.width ?? 0, height: child.height ?? 0 }
}

function routed(edge: ViewEdge | undefined, routedEdge: ElkExtendedEdge): RoutedEdge {
  if (edge === undefined) throw new Error(`ELK routed ${routedEdge.id}, which the view does not hold`)
  const points = (routedEdge.sections ?? []).flatMap((section) => [
    section.startPoint,
    ...(section.bendPoints ?? []),
    section.endPoint,
  ])
  return { edge, points }
}
