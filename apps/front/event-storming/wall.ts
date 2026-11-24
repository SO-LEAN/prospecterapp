import { lean } from './marker.ts'
import { type Elsewhere, folderOf, type Link, type Model, type Sticky, type StickyType } from './model.ts'

/** The post-its of the wall: the seven of the model, and the hotspot that says something is missing. */
export type Kind = StickyType | 'hotspot'

/**
 * A post-it on the wall, placed in its step. A step repeats an aggregate, an actor's badge or a port for each use
 * case, so that each story reads alone: the key tells the copies apart, the slug names the post-it of the model.
 */
export interface Note {
  key: string
  slug: string
  kind: Kind
  label: string
  sticky: Sticky | null
  step: Step
  x: number
  y: number
  w: number
  h: number
  /** Which post-it lies on top of which: the higher paints over the lower. */
  z: number
  /** What its lean comes from: its slug, and where its copy stands, never its rank on the wall. */
  seed: string
  tilt: number
  /** An external system tucked under its use case, a narrow strip: the use case and its rank under it. */
  port: boolean
  owner: Note | null
  slot: number
  /** The member's AI, a small round badge on each use case it calls through its tool. */
  badge: boolean
  /** A post-it of another context, on a piece of its paper. */
  foreign: boolean
  published: boolean
  /** The aggregate's method its command calls. */
  via: string | null
  /** The events a policy hears. */
  whenever: string | null
  /** Why a hotspot stands there. */
  why: string | null
  jumps: Jump[]
}

/**
 * A pictogram on a post-it that says the story goes on elsewhere on the wall, on another context's wall (its
 * address), or who answers a port.
 */
export interface Jump {
  to: Note | null
  href: string | null
  back: boolean
  tip: string
}

/** A marker arrow of a reaction (drawn), or the touch of two post-its of a step (not drawn). */
export interface Edge {
  from: Note
  to: Note
  certain: boolean
  drawn: boolean
  when: string | null
}

/** The marker line from an actor or a view that branches to the commands it leads to. */
export interface Bracket {
  from: Note
  side: 'right' | 'over'
  beside: Note | null
  items: BracketItem[]
  lane: Lane
  shift: number
}

export interface BracketItem {
  to: Note
  command: Note
  certain: boolean
  /** The condition of a dashed line, shown on hover and on focus. */
  tip: string | null
}

/** A piece of another context's paper under one of its post-its. */
export interface Strip {
  note: Note
  x: number
  y: number
  w: number
  h: number
  context: string
  tilt: number
}

/** What a view offers to a command, read from the links of its presenter. */
export interface Offer {
  view: Note
  via: string | null
  certain: boolean
  when: string | null
}

export type StepKind = 'read' | 'command' | 'policy' | 'actor'

/** A cluster of post-its that touch: who asks, what is asked, what it changes, what happened. */
export interface Step {
  kind: StepKind
  lane: string
  /** The order of its kind among the rows of a lane. */
  order: number
  notes: Note[]
  strips: Strip[]
  /** The events of this wall it waits for. */
  waitsFor: string[]
  main: Note | null
  width: number
  senders: Note[]
  offers: Offer[]
  /** Room left before the step for its hotspot. */
  shift: number
  gap: Note | null
  headroom: number
}

export interface Group {
  actor: Note
  reads: Step[]
  commands: Step[]
  solo: Step | null
}

export interface Lane {
  name: string
  label: string
  steps: Step[]
  groups: Group[]
}

export interface Wall {
  context: string
  notes: Note[]
  edges: Edge[]
  brackets: Bracket[]
  steps: Step[]
  lanes: Lane[]
}

/** The size of the post-its at real size, and the room between them. */
export const SIZE = {
  sticky: 132,
  actor: 70,
  port: { w: 160, h: 47 },
  hotspot: 104,
  badge: 32,
  /** The room an arrow of a reaction runs across. */
  arrow: 66,
  /** The room between two steps of a row. */
  gap: 92,
  /** The room left of the steps, for the lane's tape. */
  margin: 250,
} as const

/**
 * How the post-its of a step touch, from the command's corner: the aggregate under its right edge, a little higher;
 * the event it records under the aggregate's right edge; an event the command publishes itself against its right
 * edge; the next aggregate or event one row lower; a hotspot a little apart.
 */
const CLUSTER = {
  aggregate: { x: SIZE.sticky - 15, y: -13, pitch: SIZE.sticky + 14 },
  recorded: { x: 2 * SIZE.sticky - 32 },
  published: { x: SIZE.sticky - 3 },
  event: { y: 7, pitch: 100 },
  hotspot: { x: 13, y: 22 },
} as const

/** What a hotspot of something nothing reaches says of itself. */
const SUSPICION =
  'It is a suspicion to check: the analysis follows the calls it reads, and a dynamic call escapes it.'

/** The ports under a use case: their left edge, their stagger every other one, the room above them. */
const PORTS = { left: 6, stagger: 7, room: 4 } as const

/** Where a lane's view stands, right of its actor, and where its commands stand, right of the view. */
export const VIEW = SIZE.margin + SIZE.actor - 1
export const HEAD = VIEW + SIZE.sticky + 86

/** The actors that call a use case through a tool, without looking at a screen: a badge on each use case. */
const TOOLS = new Set(['mcp'])

/**
 * The lane of the use cases at the application's root, which belong to no module. A context without modules has
 * only this lane, named after the context.
 */
const ROOT = 'Application'

export function wallOf(model: Model): Wall {
  return new WallUnderConstruction(model).built()
}

/** "PersonalData" reads "Personal data". */
export function sentenceOf(name: string): string {
  const words = name.split(/(?<=[a-z0-9])(?=[A-Z])/)
  return words.map((word, index) => (index === 0 ? word : word.toLowerCase())).join(' ')
}

class WallUnderConstruction {
  private readonly model: Model
  private readonly byId: Map<string, Sticky>
  private readonly notes: Note[] = []
  private readonly edges: Edge[] = []
  private readonly brackets: Bracket[] = []
  private readonly steps: Step[] = []
  private readonly firstOf = new Map<string, Note>()
  private readonly heardBefore = new Map<Step, { event: Note; policy: Sticky; certain: boolean }[]>()
  private readonly pending: { from: Note; to: string; certain: boolean }[] = []

  constructor(model: Model) {
    this.model = model
    this.byId = new Map(model.stickies.map((sticky) => [sticky.id, sticky]))
  }

  built(): Wall {
    const root = this.model.modules.length === 0 ? this.model.context : ROOT
    const mine = (type: StickyType): Sticky[] =>
      this.model.stickies.filter((sticky) => sticky.type === type && sticky.context === this.model.context)
    for (const readModel of mine('readModel')) this.readStep(readModel, readModel.module ?? root)
    for (const command of mine('command')) this.commandStep(command, command.module ?? root)
    for (const policy of mine('policy')) {
      if (this.notes.some((note) => note.sticky === policy)) continue
      const sent = this.out(policy.id, 'command')[0]
      this.policyStep(policy, policy.module ?? (sent ? this.sticky(sent.to).module : null) ?? root)
    }
    this.wireTheReactions()
    const lanes = this.lanes()
    for (const lane of lanes) this.standTheActors(lane)
    return {
      context: this.model.context,
      notes: this.notes,
      edges: this.edges,
      brackets: this.brackets,
      steps: this.steps,
      lanes,
    }
  }

  private sticky(id: string): Sticky {
    const found = this.byId.get(id)
    if (found === undefined) throw new Error(`The model links ${id}, which it does not hold`)
    return found
  }

  private out(id: string, type: StickyType): Link[] {
    return this.model.links.filter((link) => link.from === id && this.sticky(link.to).type === type)
  }

  private into(id: string, type: StickyType): Link[] {
    return this.model.links.filter((link) => link.to === id && this.sticky(link.from).type === type)
  }

  private step(kind: StepKind, lane: string, order: number): Step {
    const step: Step = {
      kind,
      lane,
      order,
      notes: [],
      strips: [],
      waitsFor: [],
      main: null,
      width: 0,
      senders: [],
      offers: [],
      shift: 0,
      gap: null,
      headroom: 0,
    }
    this.steps.push(step)
    return step
  }

  private put(
    step: Step,
    sticky: Sticky | null,
    kind: Kind,
    at: { x: number; y: number },
    extra: Partial<Note> = {},
  ): Note {
    const size = sizeOf(kind, extra)
    const key = `${step.lane}#${this.steps.indexOf(step)}|${sticky?.id ?? 'hotspot'}|${extra.via ?? ''}|${step.notes.length}`
    const foreign = sticky !== null && sticky.context !== this.model.context && extra.port !== true
    const note: Note = {
      key,
      slug: sticky?.slug ?? `hotspot:${slugOfHotspot(extra.label ?? '')}@${step.main?.slug ?? ''}`,
      kind,
      label: sticky?.label ?? extra.label ?? '',
      sticky,
      step,
      ...at,
      ...size,
      z: 2,
      seed: '',
      tilt: 0,
      port: false,
      owner: null,
      slot: 0,
      badge: false,
      foreign,
      published: sticky?.slug.includes('published:') ?? false,
      via: null,
      whenever: null,
      why: null,
      jumps: [],
      ...extra,
    }
    note.seed = seedOf(note)
    note.tilt = tiltOf(note)
    step.notes.push(note)
    this.notes.push(note)
    if (sticky !== null && !this.firstOf.has(sticky.id)) this.firstOf.set(sticky.id, note)
    if (foreign) {
      step.strips.push({
        note,
        x: at.x - 11,
        y: at.y - 27,
        w: SIZE.sticky + 22,
        h: SIZE.sticky + 44,
        context: sentenceOf(sticky.context),
        tilt: lean(`${sticky.id}strip`, 1.6),
      })
    }
    return note
  }

  private link(from: Note, to: Note, certain: boolean, drawn: boolean, when: string | null = null): void {
    const known =
      from.sticky !== null && to.sticky !== null ? this.whenOf(from.sticky.id, to.sticky.id) : null
    this.edges.push({ from, to, certain, drawn, when: certain ? null : (when ?? known) })
  }

  private whenOf(from: string, to: string): string | null {
    return this.model.links.find((link) => link.from === from && link.to === to)?.when ?? null
  }

  /** The member's AI calls a use case without a screen: a badge on its corner. */
  private badgesOf(step: Step, useCase: Sticky, node: Note): void {
    step.main = node
    this.into(useCase.id, 'actor')
      .filter((asked) => TOOLS.has(this.sticky(asked.from).name))
      .forEach((asked, index) => {
        const badge = this.put(
          step,
          this.sticky(asked.from),
          'actor',
          { x: node.x - 13 + index * 26, y: node.y - 14 },
          { z: 5, badge: true },
        )
        this.link(badge, node, asked.certain, false)
      })
  }

  /**
   * The ports of a command go down under the aggregates of its later changes, which stand in their column: the first
   * aggregate ends above them.
   */
  private lowerThePortsUnderTheChanges(step: Step, node: Note): void {
    const under = node.y + SIZE.sticky - 3
    const aggregates = step.notes.filter((note) => note.kind === 'aggregate')
    const lower = Math.max(under, ...aggregates.map((note) => note.y + note.h + PORTS.room)) - under
    for (const port of step.notes.filter((note) => note.port)) port.y += lower
  }

  /** What a use case asks, tucked under it. */
  private portsOf(step: Step, useCase: Sticky, node: Note): void {
    this.out(useCase.id, 'externalSystem').forEach((asked, index) => {
      const sticky = this.sticky(asked.to)
      const port = this.put(
        step,
        sticky,
        'externalSystem',
        {
          x: node.x + PORTS.left + (index % 2) * PORTS.stagger,
          y: node.y + SIZE.sticky - 3 + index * (SIZE.port.h + 2),
        },
        { port: true, owner: node, slot: index, z: 1 },
      )
      if (sticky.answers.length > 0) {
        for (const service of sticky.answers) port.jumps.push(away(service, 'Answered by'))
      } else if (sticky.answeredBy.length > 0) {
        port.jumps.push({
          to: null,
          href: null,
          back: false,
          tip: `Answered by ${sticky.answeredBy.map(sentenceOf).join(', ')}`,
        })
      }
      this.link(node, port, asked.certain, false)
    })
  }

  /** The events of other contexts a policy hears, before it on their paper. Those of this wall are linked later. */
  private heardBy(step: Step, policy: Sticky, x: number): number {
    for (const heard of this.into(policy.id, 'event')) {
      const event = this.sticky(heard.from)
      if (this.isMadeHere(event)) continue
      const node = this.put(step, event, 'event', { x, y: 0 })
      node.jumps.push(away(foreignOf(event), 'Heard from'))
      this.heardBefore.set(step, [
        ...(this.heardBefore.get(step) ?? []),
        { event: node, policy, certain: heard.certain },
      ])
      x += SIZE.sticky + SIZE.arrow
    }
    return x
  }

  private isMadeHere(event: Sticky): boolean {
    return event.context === this.model.context && this.model.links.some((made) => made.to === event.id)
  }

  private wire(step: Step, policy: Sticky, node: Note): void {
    for (const { event, certain } of (this.heardBefore.get(step) ?? []).filter(
      (heard) => heard.policy === policy,
    )) {
      this.link(event, node, certain, true)
    }
    for (const heard of this.into(policy.id, 'event')) {
      const event = this.sticky(heard.from)
      if (!this.isMadeHere(event)) continue
      this.pending.push({ from: node, to: event.id, certain: heard.certain })
      step.waitsFor.push(event.id)
    }
  }

  /** The policies of other contexts that hear an event, after it on their paper. Returns the step's right edge. */
  private hearersOf(step: Step, event: Note): number {
    let right = event.x + SIZE.sticky
    const sticky = event.sticky
    if (sticky === null) return right
    this.out(sticky.id, 'policy')
      .filter((heard) => this.sticky(heard.to).context !== this.model.context)
      .forEach((heard, index) => {
        const policy = this.put(
          step,
          this.sticky(heard.to),
          'policy',
          { x: event.x + SIZE.sticky + SIZE.arrow + 12, y: event.y + index * (SIZE.sticky + 56) },
          { whenever: event.label },
        )
        this.link(event, policy, heard.certain, true)
        event.jumps.push(away(foreignOf(this.sticky(heard.to)), 'Heard by'))
        right = policy.x + SIZE.sticky + 12
      })
    return right
  }

  /** A policy of this context that only sends one command is drawn in line before it. */
  private sendsOnly(policy: Sticky, command: Sticky): boolean {
    return (
      policy.context === this.model.context &&
      this.out(policy.id, 'command').length === 1 &&
      this.out(policy.id, 'event').length === 0 &&
      (policy.module === null || policy.module === command.module)
    )
  }

  private wheneverOf(policy: Sticky): string {
    return this.into(policy.id, 'event')
      .map((heard) => this.sticky(heard.from).label)
      .join(' or ')
  }

  private commandStep(command: Sticky, lane: string): Step {
    const triggers = this.into(command.id, 'policy').filter((sent) =>
      this.sendsOnly(this.sticky(sent.from), command),
    )
    const step = this.step('command', lane, triggers.length > 0 ? 2 : 1)
    const { senders, x: after } = this.sendersOf(step, triggers)
    const unsent = this.into(command.id, 'actor').length === 0 && this.into(command.id, 'policy').length === 0
    const x = unsent ? after + SIZE.hotspot + CLUSTER.hotspot.x : after
    const node = this.put(step, command, 'command', { x, y: 0 }, { z: 3 })
    if (unsent) this.markNobodySends(step, node)
    for (const sender of senders) this.link(sender.node, node, sender.certain, true)
    this.badgesOf(step, command, node)
    this.portsOf(step, command, node)
    const changed = this.stickTheChanges(step, command, node)
    const published = this.stickThePublished(step, command, node, changed.rows)
    this.lowerThePortsUnderTheChanges(step, node)
    step.width =
      changed.rows + published.rows === 0
        ? this.markNoEventRecorded(step, node)
        : Math.max(x + SIZE.sticky, changed.right, published.right)
    return step
  }

  /** The policies that send a command and nothing else stand in line before it, after what they hear. */
  private sendersOf(
    step: Step,
    triggers: Link[],
  ): { senders: { node: Note; certain: boolean }[]; x: number } {
    let x = 0
    const senders = triggers.map((sent) => {
      const policy = this.sticky(sent.from)
      x = this.heardBy(step, policy, x)
      const node = this.put(step, policy, 'policy', { x, y: 0 }, { whenever: this.wheneverOf(policy) })
      this.wire(step, policy, node)
      x += SIZE.sticky + SIZE.arrow
      return { node, certain: sent.certain }
    })
    return { senders, x }
  }

  /** Each aggregate a command changes, under its edge, and the events it records, each on its own row. */
  private stickTheChanges(step: Step, command: Sticky, node: Note): { rows: number; right: number } {
    let rows = 0
    let right = 0
    for (const change of this.out(command.id, 'aggregate')) {
      const aggregate = this.put(
        step,
        this.sticky(change.to),
        'aggregate',
        { x: node.x + CLUSTER.aggregate.x, y: CLUSTER.aggregate.y + rows * CLUSTER.aggregate.pitch },
        { via: change.via, z: 1 },
      )
      this.link(node, aggregate, change.certain, false)
      right = Math.max(right, aggregate.x + SIZE.sticky)
      for (const recorded of this.out(change.to, 'event').filter((link) => link.via === change.via)) {
        const event = this.put(
          step,
          this.sticky(recorded.to),
          'event',
          { x: node.x + CLUSTER.recorded.x, y: CLUSTER.event.y + rows * CLUSTER.event.pitch },
          { z: 3 },
        )
        this.link(aggregate, event, recorded.certain, false)
        right = Math.max(right, this.hearersOf(step, event))
        rows++
      }
    }
    return { rows, right }
  }

  /** The events a command publishes itself, against its right edge, under those its aggregates record. */
  private stickThePublished(
    step: Step,
    command: Sticky,
    node: Note,
    after: number,
  ): { rows: number; right: number } {
    let rows = 0
    let right = 0
    for (const published of this.out(command.id, 'event')) {
      const event = this.put(
        step,
        this.sticky(published.to),
        'event',
        { x: node.x + CLUSTER.published.x, y: CLUSTER.event.y + (after + rows) * CLUSTER.event.pitch },
        { z: 3 },
      )
      this.link(node, event, published.certain, false)
      right = Math.max(right, this.hearersOf(step, event))
      rows++
    }
    return { rows, right }
  }

  /** A command that records no event gets a hotspot after it. Returns the step's right edge. */
  private markNoEventRecorded(step: Step, node: Note): number {
    const hotspot = this.put(
      step,
      null,
      'hotspot',
      { x: node.x + SIZE.sticky + CLUSTER.hotspot.x, y: CLUSTER.hotspot.y },
      {
        label: 'No event recorded',
        z: 4,
        why: 'This command changes no aggregate that records an event: nothing on the wall says what happened. Either an event is missing, or the generator cannot see it.',
      },
    )
    this.link(node, hotspot, true, false)
    return hotspot.x + SIZE.hotspot
  }

  /** A command no actor and no policy sends gets a hotspot before it. */
  private markNobodySends(step: Step, node: Note): void {
    const hotspot = this.put(
      step,
      null,
      'hotspot',
      { x: node.x - SIZE.hotspot - CLUSTER.hotspot.x, y: CLUSTER.hotspot.y },
      {
        label: 'Nobody sends this',
        z: 4,
        why: `No controller, no tool of the MCP server and no policy sends this command. ${SUSPICION}`,
      },
    )
    this.link(hotspot, node, true, false)
  }

  private policyStep(policy: Sticky, lane: string): Step {
    const step = this.step('policy', lane, 3)
    const x = this.heardBy(step, policy, 0)
    const node = this.put(step, policy, 'policy', { x, y: 0 }, { z: 3, whenever: this.wheneverOf(policy) })
    step.main = node
    this.wire(step, policy, node)
    let right = x + SIZE.sticky
    this.out(policy.id, 'event').forEach((published, row) => {
      const event = this.put(
        step,
        this.sticky(published.to),
        'event',
        { x: x + SIZE.sticky - 3, y: 9 + row * 100 },
        { z: 3 },
      )
      this.link(node, event, published.certain, false)
      right = Math.max(right, this.hearersOf(step, event))
    })
    for (const sent of this.out(policy.id, 'command')) {
      this.pending.push({ from: node, to: sent.to, certain: sent.certain })
    }
    step.width = right
    return step
  }

  private readStep(readModel: Sticky, lane: string): Step {
    const asked = this.into(readModel.id, 'actor').length > 0
    const step = this.step('read', lane, asked ? 0 : 4)
    const node = this.put(step, readModel, 'readModel', { x: 0, y: 0 }, { z: 3 })
    for (const reader of readModel.readBy)
      if (reader.context !== null) node.jumps.push(away(reader, 'Read by'))
    step.main = node
    this.badgesOf(step, readModel, node)
    this.portsOf(step, readModel, node)
    step.width = SIZE.sticky
    if (!asked && readModel.readBy.length === 0) {
      const hotspot = this.put(
        step,
        null,
        'hotspot',
        { x: SIZE.sticky + CLUSTER.hotspot.x, y: CLUSTER.hotspot.y },
        {
          label: 'Nobody calls this',
          z: 4,
          why: `No controller, no tool of the MCP server and no class of another context reads this view. ${SUSPICION}`,
        },
      )
      this.link(node, hotspot, true, false)
      step.width = hotspot.x + SIZE.hotspot
    }
    return step
  }

  /** The reactions of this wall: an arrow from the event to the policy that hears it, and back by a pictogram. */
  private wireTheReactions(): void {
    for (const wait of this.pending) {
      const from = this.pendingFrom(wait)
      const to = this.pendingTo(wait)
      if (from === undefined || to === undefined) continue
      this.link(from, to, wait.certain, true)
      from.jumps.push({
        to,
        href: null,
        back: false,
        tip: `${to.kind === 'policy' ? 'Heard by' : 'Sends'} “${to.label}” · ${sentenceOf(to.step.lane)}`,
      })
      to.jumps.push({
        to: from,
        href: null,
        back: true,
        tip: `${from.kind === 'event' ? 'Hears' : 'Sent by'} “${from.label}” · ${sentenceOf(from.step.lane)}`,
      })
    }
  }

  /** A policy waits for an event of this wall (the event comes first), or sends a command of it. */
  private pendingFrom(wait: { from: Note; to: string }): Note | undefined {
    return wait.from.kind === 'policy' && this.sticky(wait.to).type === 'event'
      ? this.firstOf.get(wait.to)
      : wait.from
  }

  private pendingTo(wait: { from: Note; to: string }): Note | undefined {
    return wait.from.kind === 'policy' && this.sticky(wait.to).type === 'event'
      ? wait.from
      : this.firstOf.get(wait.to)
  }

  /** The lanes: a module that hears another one sits under it, so that the arrows of its reactions point down. */
  private lanes(): Lane[] {
    const names = [...new Set([...this.model.modules, ...this.steps.map((step) => step.lane)])].filter(
      (name) => this.steps.some((step) => step.lane === name),
    )
    const rank = new Map(names.map((name) => [name, 0]))
    const rankOf = (name: string): number => rank.get(name) ?? 0
    for (let pass = 0; pass < names.length; pass++) {
      for (const edge of this.edges.filter(
        (edge) => edge.drawn && edge.from.step.lane !== edge.to.step.lane,
      )) {
        rank.set(
          edge.to.step.lane,
          Math.min(names.length, Math.max(rankOf(edge.to.step.lane), rankOf(edge.from.step.lane) + 1)),
        )
      }
    }
    const order = [...names].sort((a, b) => rankOf(a) - rankOf(b) || names.indexOf(a) - names.indexOf(b))
    return order.map((name) => ({
      name,
      label: sentenceOf(name),
      steps: this.steps.filter((step) => step.lane === name),
      groups: [],
    }))
  }

  /**
   * A person looks at a read model, then decides: in a lane, each human actor stands once, left of what it reads
   * there, and the commands that view offers hang from it. What no view of the actor offers hangs from the actor.
   */
  private standTheActors(lane: Lane): void {
    const taken = new Set<Step>()
    let shift = 0
    const humans = this.model.stickies.filter((sticky) => sticky.type === 'actor' && !TOOLS.has(sticky.name))
    for (const actor of humans) {
      const sent = lane.steps.filter(
        (step) => step.waitsFor.length === 0 && this.humansOf(step).includes(actor),
      )
      if (sent.length === 0) continue
      const mine = sent.filter((step) => !taken.has(step))
      const views = sent.filter((step) => step.kind === 'read')
      const sends = sent.filter((step) => step.kind !== 'read')
      const reads = mine.filter((step) => step.kind === 'read')
      const commands = mine.filter((step) => step.kind !== 'read')
      for (const step of mine) taken.add(step)
      const { node, solo } = this.standing(actor, lane, reads, commands)
      for (const view of views) if (view.main !== null) this.link(node, view.main, true, false)
      shift = this.hangTheOffers(lane, node, reads, sends, shift)
      shift = this.hangTheRest(lane, node, views, reads, sends, commands, shift)
      lane.groups.push({ actor: node, reads, commands, solo })
    }
  }

  private humansOf(step: Step): Sticky[] {
    const main = step.main?.sticky
    if (main === undefined || main === null) return []
    return this.into(main.id, 'actor')
      .map((asked) => this.sticky(asked.from))
      .filter((actor) => !TOOLS.has(actor.name))
  }

  private standing(
    actor: Sticky,
    lane: Lane,
    reads: Step[],
    commands: Step[],
  ): { node: Note; solo: Step | null } {
    const [view] = reads
    if (view !== undefined) {
      return { node: this.put(view, actor, 'actor', { x: -(SIZE.actor - 1), y: 30 }, { z: 4 }), solo: null }
    }
    const [command] = commands
    if (command?.main) {
      return {
        node: this.put(
          command,
          actor,
          'actor',
          { x: command.main.x - (HEAD - SIZE.margin), y: 30 },
          { z: 4 },
        ),
        solo: null,
      }
    }
    const solo = this.step('actor', lane.name, 1)
    lane.steps.push(solo)
    solo.width = SIZE.actor
    return { node: this.put(solo, actor, 'actor', { x: 0, y: 30 }, { z: 4 }), solo }
  }

  /** What a view the actor reads offers hangs from that view. */
  private hangTheOffers(lane: Lane, actor: Note, reads: Step[], sends: Step[], shift: number): number {
    for (const view of reads) {
      const main = view.main
      if (main?.sticky === null || main === null) continue
      const offered = this.out(main.sticky.id, 'command')
        .map((offer) => ({ offer, step: sends.find((step) => step.main?.sticky?.id === offer.to) }))
        .filter(
          (item): item is { offer: Link; step: Step } =>
            item.step?.main !== undefined && item.step.main !== null,
        )
      if (offered.length === 0) continue
      const items = offered.map(({ offer, step }) => {
        const command = step.main as Note
        this.link(main, command, offer.certain, false, offer.when)
        step.offers.push({ view: main, via: offer.via, certain: offer.certain, when: offer.when })
        step.senders.push(actor)
        return {
          to: command,
          command,
          certain: offer.certain,
          tip: offer.certain ? null : `Offered only ${offer.when ?? 'in some states'}`,
        }
      })
      this.brackets.push({ from: main, side: 'right', beside: null, items, lane, shift: shift++ })
    }
    return shift
  }

  /** What no view of the actor offers hangs from the actor itself. It is a gap only when the actor reads a view here. */
  private hangTheRest(
    lane: Lane,
    actor: Note,
    views: Step[],
    reads: Step[],
    sends: Step[],
    commands: Step[],
    shift: number,
  ): number {
    const offeredBy = (step: Step): boolean =>
      views.some(
        (view) =>
          view.main?.sticky !== null &&
          view.main?.sticky !== undefined &&
          this.out(view.main.sticky.id, 'command').some((offer) => offer.to === step.main?.sticky?.id),
      )
    const direct = sends.filter((step) => !offeredBy(step))
    for (const step of direct) {
      const main = step.main as Note
      this.link(actor, main, true, false)
      step.senders.push(actor)
      if (views.length > 0 && step.gap === null && commands.includes(step))
        this.openAGap(step, actor, views, main)
    }
    if (direct.length === 0) return shift
    const [view] = reads
    if (view !== undefined) view.headroom = 30
    this.brackets.push({
      from: actor,
      side: view !== undefined ? 'over' : 'right',
      beside: view?.main ?? null,
      items: direct.map((step) => ({
        to: step.gap ?? (step.main as Note),
        command: step.main as Note,
        certain: true,
        tip: null,
      })),
      lane,
      shift,
    })
    return shift + 1
  }

  private openAGap(step: Step, actor: Note, views: Step[], command: Note): void {
    step.shift = SIZE.hotspot + 24
    const viewed = views.map((view) => `“${view.main?.label ?? ''}”`).join(', ')
    step.gap = this.put(
      step,
      null,
      'hotspot',
      { x: command.x - step.shift, y: 14 },
      {
        label: 'Offered where?',
        z: 4,
        why: `${actor.label} reads ${viewed} in this module, and that resource carries no link to this command. The front must know its address by itself. Either a link is missing in the API, or the command is reached another way.`,
      },
    )
    this.link(command, step.gap, true, false)
  }
}

/** A jump to a post-it of another context's wall: its address, its label and its context on the pictogram. */
function away(elsewhere: Elsewhere, verb: string): Jump {
  const context = elsewhere.context ?? ''
  return {
    to: null,
    href: `../${folderOf(context)}/#at=${encodeURIComponent(elsewhere.slug ?? '')}`,
    back: false,
    tip: `${verb} “${elsewhere.label}” · ${sentenceOf(context)}`,
  }
}

/** A post-it of another context, as its own wall names it: its slug without its context. */
function foreignOf(sticky: Sticky): Elsewhere {
  return { context: sticky.context, slug: sticky.slug.replace(/^[^/]*\//, ''), label: sticky.label }
}

function sizeOf(kind: Kind, extra: Partial<Note>): { w: number; h: number } {
  if (extra.badge === true) return { w: SIZE.badge, h: SIZE.badge }
  if (kind === 'actor') return { w: SIZE.actor, h: SIZE.actor }
  if (extra.port === true) return SIZE.port
  if (kind === 'hotspot') return { w: SIZE.hotspot, h: SIZE.hotspot }
  return { w: SIZE.sticky, h: SIZE.sticky }
}

function seedOf(note: Note): string {
  const copy = [note.via, note.owner?.slug, note.badge ? note.step.main?.slug : null].filter(
    (part) => part !== null && part !== undefined,
  )
  return [note.slug, note.step.lane, ...copy].join('|')
}

/** How much a post-it leans comes from its seed, never from chance. */
function tiltOf(note: Note): number {
  if (note.badge) return lean(note.seed, 8)
  const amplitude = note.kind === 'actor' ? 4 : note.kind === 'hotspot' ? 2 : 2.3
  return lean(note.seed, amplitude) + (note.kind === 'hotspot' ? -6 : 0)
}

function slugOfHotspot(label: string): string {
  return label
    .toLowerCase()
    .replace(/[^a-z]+/g, '-')
    .replace(/^-|-$/g, '')
}
