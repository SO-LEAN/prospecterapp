import { escaped } from './html.ts'
import { type Edge, type Kind, type Note, type Step, sentenceOf, type Wall } from './wall.ts'

/**
 * What lights with a post-it: its story from end to end, upstream and downstream. The walk stops at an actor and at a
 * port, which every story shares. An actor or a port lights every place it stands and what it touches there.
 */
export function storyOf(wall: Wall, note: Note): Set<Note> {
  if (isShared(note)) return everywhereOf(wall, note)
  const lit = new Set<Note>([note])
  walk(wall, note, lit, 'downstream')
  walk(wall, note, lit, 'upstream')
  for (const member of [...lit]) {
    if (member.kind !== 'command' && member.kind !== 'readModel') continue
    for (const edge of wall.edges) if (edge.from === member && edge.to.port) lit.add(edge.to)
  }
  return lit
}

function isShared(note: Note): boolean {
  return note.kind === 'actor' || note.port
}

function everywhereOf(wall: Wall, note: Note): Set<Note> {
  const twins = wall.notes.filter((other) => other.sticky === note.sticky)
  const touched = wall.edges
    .filter((edge) => twins.includes(edge.from) || twins.includes(edge.to))
    .flatMap((edge) => [edge.from, edge.to])
  return new Set([...twins, ...touched])
}

function walk(wall: Wall, start: Note, lit: Set<Note>, direction: 'downstream' | 'upstream'): void {
  const queue = [start]
  for (let at = queue.pop(); at !== undefined; at = queue.pop()) {
    for (const edge of wall.edges) {
      const next =
        direction === 'downstream' ? (edge.from === at ? edge.to : null) : edge.to === at ? edge.from : null
      if (next === null || lit.has(next)) continue
      lit.add(next)
      if (!isShared(next)) queue.push(next)
    }
  }
}

/** The names of the post-its, as the legend and the card write them. */
export const KIND_NAMES: Record<Kind, string> = {
  actor: 'Actor',
  command: 'Command',
  aggregate: 'Aggregate',
  event: 'Event',
  policy: 'Policy',
  readModel: 'Read model',
  externalSystem: 'External system',
  hotspot: 'Hotspot',
}

const CORE = 'Prospecter\\Core\\'
const SOURCES = 'https://github.com/SO-LEAN/prospecterapp/blob/dev/packages/core/src/'

/** A post-it named in a card or a sentence: a button that flies to it. */
export function chipOf(wall: Wall, note: Note, text = note.label): string {
  return `<button class="go" data-go="${wall.notes.indexOf(note)}"><span class="dot" style="--c:var(--${note.kind})"></span>${escaped(text)}</button>`
}

/**
 * The card of a pinned post-it: what it is, where it stands, its feature, what comes before and after it, who
 * sends it and from which view, the rights its aggregates check, its source, and the link to copy.
 */
export function cardOf(wall: Wall, note: Note): string {
  const { sticky } = note
  const twins = isShared(note) ? wall.notes.filter((other) => other.sticky === sticky) : [note]
  const before = wall.edges.filter((edge) => twins.includes(edge.to)).map((edge) => edge.from)
  const after = wall.edges.filter((edge) => twins.includes(edge.from)).map((edge) => edge.to)
  const chips = (notes: Note[]): string => notes.map((other) => chipOf(wall, other)).join('<br>')
  const rows = [
    row('Where', whereOf(wall, note, twins.length)),
    row('Feature', sticky?.feature ? `<span lang="fr">« ${escaped(sticky.feature)} »</span>` : ''),
    row('Answered by', note.port ? escaped((sticky?.answeredBy ?? []).map(sentenceOf).join(', ')) : ''),
    row('Before', chips(before)),
    row(note.kind === 'actor' ? 'Can' : 'Then', note.port ? '' : chips(after)),
    row('Offered by', note.step.main === note ? offersOf(wall, note) : ''),
    row('Access refusals', escaped((sticky?.rights ?? []).join(', '))),
    row('Source', sourceOf(note)),
  ]
  const conditions = wall.edges
    .filter((edge) => !edge.certain && (edge.from === note || edge.to === note))
    .map(
      (edge) =>
        `<p class="note">Dashed, from “${escaped(edge.from.label)}” to “${escaped(edge.to.label)}”: only ${escaped(edge.when ?? 'in some states')}.</p>`,
    )
  return [
    `<h2><span class="kind"><span class="dot" style="--c:var(--${note.kind})"></span>${KIND_NAMES[note.kind]}${note.published ? ' · published to other contexts' : ''}</span><button class="btn icon close" data-close="inspector" aria-label="Close">${CROSS}</button></h2>`,
    `<div class="name">${escaped(note.label)}</div>`,
    note.why === null ? '' : `<p class="sentence">${escaped(note.why)}</p>`,
    sticky === null ? '' : `<code>${escaped(sticky.class ?? sticky.name)}</code>`,
    `<dl>${rows.join('')}</dl>`,
    isShared(note) || note.foreign ? '' : `<p class="sentence">${sentenceAbout(wall, note.step)}</p>`,
    ...conditions,
    '<p class="note"><button class="go" data-copy>Copy the link to this post-it</button></p>',
  ].join('')
}

const CROSS = '<svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>'

function row(term: string, html: string): string {
  return html === '' ? '' : `<dt>${term}</dt><dd>${html}</dd>`
}

function whereOf(wall: Wall, note: Note, places: number): string {
  if (note.foreign && note.sticky !== null) return `${escaped(sentenceOf(note.sticky.context))}'s wall`
  if (isShared(note)) return `${places} places on this wall`
  const { lane, module } = note.step
  const where = `${escaped(wall.context)} › ${escaped(sentenceOf(module))}`
  return lane === module
    ? where
    : `${where}, in the lane of ${escaped(sentenceOf(lane))}, whose event it hears`
}

function offersOf(wall: Wall, note: Note): string {
  return note.step.offers
    .map(
      (offer) =>
        `${chipOf(wall, offer.view)} <small>link <code>${escaped(offer.via ?? '')}</code>${offer.certain ? '' : `, only ${escaped(offer.when ?? 'in some states')}`}</small>`,
    )
    .join('<br>')
}

function sourceOf(note: Note): string {
  const name = note.sticky?.class
  if (name === null || name === undefined || !name.startsWith(CORE)) return ''
  const path = `${name.slice(CORE.length).replaceAll('\\', '/')}.php`
  return `<a href="${SOURCES}${escaped(path)}" target="_blank" rel="noopener">${escaped(path.split('/').at(-1) ?? path)}</a>`
}

/** A step told in one sentence: who asks, from which view, what it asks, what it changes, what happened. */
export function sentenceAbout(wall: Wall, step: Step): string {
  const of = (kind: Kind): Note[] => step.notes.filter((note) => note.kind === kind && !note.port)
  const list = (notes: Note[], joiner = ' or '): string =>
    notes.map((note) => chipOf(wall, note)).join(joiner)
  const parts: string[] = []
  for (const policy of of('policy').filter((note) => !note.foreign))
    parts.push(reactionSentence(wall, step, policy))
  const main = of('command')[0] ?? of('readModel')[0]
  if (main !== undefined) parts.push(sentenceOfTheUseCase(wall, step, main, list))
  for (const hearer of of('policy').filter((note) => note.foreign && note.sticky !== null)) {
    parts.push(`${escaped(sentenceOf(hearer.sticky?.context ?? ''))} hears it: ${chipOf(wall, hearer)}.`)
  }
  for (const hotspot of onceEach(of('hotspot'), (note) => note.label))
    parts.push(chipOf(wall, hotspot, `?! ${hotspot.label}`))
  return parts.join(' ')
}

function reactionSentence(wall: Wall, step: Step, policy: Note): string {
  const does = wall.edges
    .filter((edge) => edge.from === policy && edge.to.kind !== 'actor')
    .map((edge) => `${sendingOf(edge)} ${chipOf(wall, edge.to)}`)
    .join(' and ')
  return `Whenever ${heardBy(wall, step, policy)}, ${chipOf(wall, policy)} ${does}.`
}

/** The events a policy hears, each with the context or the lane it comes from when it is not its step's. */
function heardBy(wall: Wall, step: Step, policy: Note): string {
  const hears = wall.edges
    .filter((edge) => edge.to === policy && edge.drawn)
    .map(
      (edge) =>
        chipOf(wall, edge.from) +
        (edge.from.foreign
          ? ` (${escaped(sentenceOf(edge.from.sticky?.context ?? ''))})`
          : edge.from.step !== step
            ? ` (${escaped(sentenceOf(edge.from.step.lane))})`
            : ''),
    )
    .join(' or ')
  return hears || 'something happens'
}

/** How a policy sends what it leads to: always, or only in some states. */
function sendingOf(edge: Edge): string {
  if (!edge.certain) return `may send (only ${escaped(edge.when ?? 'in some states')})`
  return edge.to.kind === 'event' ? 'publishes' : 'sends'
}

/** What starts a command that no actor sends: the policy of another step that sends it, or nothing said. */
function causeOf(wall: Wall, step: Step, main: Note): string {
  const sent = wall.edges.find(
    (edge) => edge.to === main && edge.from.kind === 'policy' && edge.from.step !== step,
  )
  if (sent === undefined) return 'Then '
  return `Whenever ${heardBy(wall, sent.from.step, sent.from)}, ${chipOf(wall, sent.from)} ${sendingOf(sent)} `
}

function sentenceOfTheUseCase(
  wall: Wall,
  step: Step,
  main: Note,
  list: (notes: Note[], joiner?: string) => string,
): string {
  const actors = [
    ...new Set([
      ...step.senders,
      ...wall.edges.filter((edge) => edge.to === main && edge.from.kind === 'actor').map((edge) => edge.from),
    ]),
  ]
  const ports = step.notes.filter((note) => note.port)
  const views = step.offers.map(
    (offer) =>
      `${chipOf(wall, offer.view)}${offer.certain ? '' : ` (only ${escaped(offer.when ?? 'in some states')})`}`,
  )
  const changes = changesIn(wall, step)
  const who =
    actors.length > 0
      ? `${list(actors)}${views.length > 0 ? `, from ${views.join(' or ')},` : ''} ${main.kind === 'command' ? 'asks to' : 'looks at'} `
      : main.kind === 'command'
        ? causeOf(wall, step, main)
        : ''
  const asks = ports.length > 0 ? `, which asks ${list(ports, ', ')}` : ''
  return `${who}${chipOf(wall, main)}${asks}.${changes.length > 0 ? ` ${changes.join(' ')}` : ''}`
}

/** What each aggregate of a step becomes: the events it records, or the methods that change it without one. */
function changesIn(wall: Wall, step: Step): string[] {
  const aggregates = step.notes.filter((note) => note.kind === 'aggregate')
  return onceEach(aggregates, (aggregate) => aggregate.sticky).map((first) => {
    const copies = aggregates.filter((aggregate) => aggregate.sticky === first.sticky)
    const recorded = wall.edges.filter((edge) => copies.includes(edge.from) && edge.to.kind === 'event')
    const edges = recorded.filter(
      (edge, index) => recorded.findIndex((other) => other.to.sticky === edge.to.sticky) === index,
    )
    const aggregate = chipOf(wall, first)
    if (edges.length > 0) return `${aggregate} ${whatItRecords(wall, edges)}.`
    const methods = inWords(copies.map((copy) => escaped(`${copy.via ?? ''}()`)))
    return `${aggregate} changes through ${methods}, and records no event.`
  })
}

/**
 * The events an aggregate always records, the arms of a choice that record one event or another, and the events it
 * may record: "records a and b or c, and may record d". When only one arm of a choice records events, they read as
 * conditional.
 */
function whatItRecords(wall: Wall, edges: Edge[]): string {
  const choices = Map.groupBy(
    edges.filter((edge) => edge.arm !== null),
    (edge) => edge.arm?.choice,
  )
  const alternatives = [...choices.values()].filter(
    (arms) => new Set(arms.map((edge) => edge.arm?.index)).size > 1,
  )
  const chips = (some: Edge[]): string => inWords(some.map((edge) => chipOf(wall, edge.to)))
  const always = [
    ...edges.filter((edge) => edge.certain).map((edge) => chipOf(wall, edge.to)),
    ...alternatives.map((arms) =>
      [...Map.groupBy(arms, (edge) => edge.arm?.index).values()].map(chips).join(' or '),
    ),
  ]
  const maybe = edges.filter((edge) => !edge.certain && !alternatives.flat().includes(edge))
  const records = always.length > 0 ? `records ${inWords(always)}` : ''
  const mayRecord = maybe.length > 0 ? `may record ${chips(maybe)}` : ''
  return [records, mayRecord].filter((part) => part !== '').join(', and ')
}

/** The first of the notes that share a key, in their order. */
function onceEach(notes: Note[], keyOf: (note: Note) => unknown): Note[] {
  return notes.filter((note, index) => notes.findIndex((other) => keyOf(other) === keyOf(note)) === index)
}

/** "a", "a and b", "a, b and c". */
function inWords(items: string[]): string {
  return items.length < 2 ? items.join('') : `${items.slice(0, -1).join(', ')} and ${items.at(-1)}`
}
