import type { Link, Model, Sticky, StickyType } from './model.ts'

/** A post-it as one view shows it. A view repeats an aggregate for each use case, so that each chain reads alone. */
export interface ViewNode {
  key: string
  type: StickyType | 'module'
  label: string
  name: string
  hover: string
  detail: string | null
  faded: boolean
}

export interface ViewEdge {
  from: string
  to: string
  certain: boolean
}

export interface View {
  id: string
  title: string
  nodes: ViewNode[]
  edges: ViewEdge[]
}

const USE_CASES: StickyType[] = ['command', 'readModel', 'policy']

export function viewsOf(model: Model): View[] {
  const useCases = model.stickies.filter(
    (sticky) => USE_CASES.includes(sticky.type) && sticky.context === model.context,
  )
  const modules = model.modules.map((module) =>
    chainsOf(
      model,
      `module:${module}`,
      module,
      useCases.filter((useCase) => useCase.module === module),
    ),
  )
  const overview =
    model.modules.length === 0
      ? chainsOf(model, OVERVIEW, OVERVIEW_TITLE, useCases)
      : overviewOf(model, useCases)
  return [overview, ...modules]
}

const OVERVIEW = 'overview'
const OVERVIEW_TITLE = "Vue d'ensemble"

/** The modules of a context, the events that go from one to another, and the use cases at its root. */
function overviewOf(model: Model, useCases: Sticky[]): View {
  const view = new ViewUnderConstruction(model)
  const homeOf = (id: string): string => {
    const sticky = view.sticky(id)
    return sticky.module === null || sticky.context !== model.context ? id : `module:${sticky.module}`
  }
  for (const module of model.modules) {
    view.addModule(module, useCases.filter((useCase) => useCase.module === module).length)
  }
  for (const event of model.stickies.filter((sticky) => sticky.type === 'event')) {
    const producers = useCases
      .filter((useCase) => view.eventsProducedBy(useCase).includes(event.id))
      .map((useCase) => homeOf(useCase.id))
    for (const listener of view.linksFrom(event, 'policy')) {
      const to = homeOf(listener.to)
      const from = producers.filter((producer) => producer !== to)
      if (from.length === 0 && producers.length > 0) continue
      view.add(event.id, event, producers.length === 0)
      if (to === listener.to) view.add(to, view.sticky(to), view.sticky(to).context !== model.context)
      for (const producer of from) view.connect(producer, event.id, true)
      view.connect(event.id, to, listener.certain)
    }
  }
  for (const root of useCases.filter((useCase) => useCase.module === null)) {
    view.add(root.id, root)
    for (const sent of view.linksFrom(root, 'command')) view.connect(root.id, homeOf(sent.to), sent.certain)
  }
  return view.done(OVERVIEW, OVERVIEW_TITLE)
}

/** The chains of a view's use cases: who sends them, what they change, what happens then. */
function chainsOf(model: Model, id: string, title: string, useCases: Sticky[]): View {
  const view = new ViewUnderConstruction(model)
  const inTheView = new Set(useCases.map((useCase) => useCase.id))
  const producedHere = new Set(useCases.flatMap((useCase) => view.eventsProducedBy(useCase)))
  for (const useCase of useCases) {
    for (const sender of view.linksTo(useCase, 'actor')) view.before(sender)
    for (const sender of view.linksTo(useCase, 'policy').filter((link) => !inTheView.has(link.from))) {
      for (const heard of view.linksTo(view.sticky(sender.from), 'event')) view.before(heard, true)
      view.before(sender, true)
    }
    for (const heard of view.linksTo(useCase, 'event')) view.before(heard, !producedHere.has(heard.from))
    view.add(useCase.id, useCase)
    for (const asked of view.linksFrom(useCase, 'externalSystem')) view.after(asked)
    for (const published of view.linksFrom(useCase, 'event')) view.after(published)
    for (const sent of view.linksFrom(useCase, 'command'))
      view.after(sent, sent.from, !inTheView.has(sent.to))
    for (const change of view.linksFrom(useCase, 'aggregate')) {
      const aggregate = `${change.to}@${useCase.id}`
      view.after(change, useCase.id, false, aggregate)
      for (const recorded of view
        .linksFrom(view.sticky(change.to), 'event')
        .filter((link) => link.via === change.via)) {
        view.after(recorded, aggregate)
      }
    }
  }
  for (const event of producedHere) {
    for (const listener of view
      .linksFrom(view.sticky(event), 'policy')
      .filter((link) => !inTheView.has(link.to))) {
      view.after(listener, event, true)
    }
  }
  return view.done(id, title)
}

class ViewUnderConstruction {
  private readonly nodes = new Map<string, ViewNode>()
  private readonly edges: ViewEdge[] = []

  private readonly model: Model

  constructor(model: Model) {
    this.model = model
  }

  sticky(id: string): Sticky {
    const found = this.model.stickies.find((sticky) => sticky.id === id)
    if (found === undefined) throw new Error(`The model links ${id}, which it does not hold`)
    return found
  }

  linksFrom(sticky: Sticky, type: StickyType): Link[] {
    return this.model.links.filter((link) => link.from === sticky.id && this.sticky(link.to).type === type)
  }

  linksTo(sticky: Sticky, type: StickyType): Link[] {
    return this.model.links.filter((link) => link.to === sticky.id && this.sticky(link.from).type === type)
  }

  /** The events a use case records through its aggregates, or publishes itself. */
  eventsProducedBy(useCase: Sticky): string[] {
    const recorded = this.linksFrom(useCase, 'aggregate').flatMap((change) =>
      this.linksFrom(this.sticky(change.to), 'event')
        .filter((link) => link.via === change.via)
        .map((link) => link.to),
    )
    return [...recorded, ...this.linksFrom(useCase, 'event').map((link) => link.to)]
  }

  /** Puts the post-it a link comes from before the one it goes to. */
  before(link: Link, faded = false): void {
    this.add(link.from, this.sticky(link.from), faded)
    this.connect(link.from, link.to, link.certain)
  }

  /** Puts the post-it a link goes to after a post-it of the view, under its own key or the one given. */
  after(link: Link, from = link.from, faded = false, key = link.to): void {
    this.add(key, this.sticky(link.to), faded)
    this.connect(from, key, link.certain)
  }

  addModule(module: string, useCases: number): void {
    const name = `${useCases} cas d'usage`
    this.nodes.set(`module:${module}`, {
      key: `module:${module}`,
      type: 'module',
      label: module,
      name,
      hover: name,
      detail: null,
      faded: false,
    })
  }

  add(key: string, sticky: Sticky, faded = false): void {
    if (this.nodes.has(key)) return
    const hover = [sticky.class ?? sticky.name, sticky.detail].filter((part) => part !== null).join(' — ')
    const { type, label, name } = sticky
    const elsewhere = sticky.context === this.model.context ? null : sticky.context
    const detail = [elsewhere, sticky.detail].filter((part) => part !== null).join(' · ') || null
    this.nodes.set(key, { key, type, label, name, hover, detail, faded })
  }

  connect(from: string, to: string, certain: boolean): void {
    if (!this.edges.some((edge) => edge.from === from && edge.to === to))
      this.edges.push({ from, to, certain })
  }

  done(id: string, title: string): View {
    return { id, title, nodes: [...this.nodes.values()], edges: this.edges }
  }
}
