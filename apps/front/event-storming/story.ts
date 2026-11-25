import { escaped } from './html.ts'
import { type Kind, type Note, type Step, sentenceOf, type Wall } from './wall.ts'

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
 * sends it and from which view, its source, and the link to copy.
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
  for (const hotspot of of('hotspot')) parts.push(chipOf(wall, hotspot, `?! ${hotspot.label}`))
  return parts.join(' ')
}

function reactionSentence(wall: Wall, step: Step, policy: Note): string {
  const heard = wall.edges.filter((edge) => edge.to === policy && edge.drawn)
  const sent = wall.edges.filter((edge) => edge.from === policy && edge.to.kind !== 'actor')
  const hears = heard
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
  const does = sent
    .map(
      (edge) =>
        `${edge.certain ? (edge.to.kind === 'event' ? 'publishes' : 'sends') : `may send (only ${escaped(edge.when ?? 'in some states')})`} ${chipOf(wall, edge.to)}`,
    )
    .join(' and ')
  return `Whenever ${hears || 'something happens'}, ${chipOf(wall, policy)} ${does}.`
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
  const changes = step.notes
    .filter((note) => note.kind === 'aggregate')
    .map(
      (aggregate) =>
        `${chipOf(wall, aggregate)} records ${list(
          wall.edges.filter((edge) => edge.from === aggregate).map((edge) => edge.to),
          ' and ',
        )}.`,
    )
  const who =
    actors.length > 0
      ? `${list(actors)}${views.length > 0 ? `, from ${views.join(' or ')},` : ''} ${main.kind === 'command' ? 'asks to' : 'looks at'} `
      : main.kind === 'command'
        ? 'Then '
        : ''
  const asks = ports.length > 0 ? `, which asks ${list(ports, ', ')}` : ''
  return `${who}${chipOf(wall, main)}${asks}.${changes.length > 0 ? ` ${changes.join(' ')}` : ''}`
}
