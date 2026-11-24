import { escaped } from './html.ts'
import { folderOf, type Model, type Sticky } from './model.ts'
import type { Assets } from './page.ts'
import { sentenceOf } from './wall.ts'

/** An event one context publishes, heard by a policy of another one. */
interface Published {
  from: string
  event: Sticky
  to: string
  policy: Sticky
}

/** A published service of one context, asked by a port of another one. */
interface Asked {
  from: string
  port: Sticky
  to: string
  service: { slug: string; label: string }
}

/** The drawing of the contexts: each on a circle, and the arrows between them. */
const MAP = { width: 760, height: 560, radius: 210, box: { w: 150, h: 52 } } as const

/**
 * The contents of the event storming, `/docs/event-storming/`: each context and its wall, and what goes from one
 * context to another, the events their policies hear and the published services their ports ask. It is read from the
 * models, as each wall is: the subscriptions (`subscriptions.json`) gave each wall the policies of the other
 * contexts that hear its events.
 */
export function overviewOf(models: Model[], assets: Assets): string {
  const sorted = [...models].sort((a, b) => a.context.localeCompare(b.context, 'en'))
  const published = sorted.flatMap(publishedBy)
  const asked = sorted.flatMap(askedBy)
  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Prospecter's event storming</title>
<style>
@font-face { font-family: "Wall Hand"; src: url(data:font/woff2;base64,${assets.font}) format("woff2"); font-display: block; }
${STYLE}
</style>
</head>
<body>
<main>
<h1>Prospecter's event storming</h1>
<p class="lead">Each bounded context's wall, drawn from its code (<code>make event-storming</code>). An arrow goes from a context to another one when the other's policies hear its events, or when it asks the other's published services.</p>
${mapOf(sorted, published, asked)}
<section id="contexts"><h2>The walls</h2><ul>${sorted.map(contextOf).join('')}</ul></section>
<section id="events"><h2>The events heard by another context</h2>${tableOf(['Published by', 'Event', 'Heard by', 'Policy'], published.map(publishedRow))}</section>
<section id="services"><h2>The published services asked by another context</h2>${tableOf(['Asked by', 'Port', 'Answered by', 'Service'], asked.map(askedRow))}</section>
</main>
</body>
</html>
`
}

function publishedBy(model: Model): Published[] {
  const byId = new Map(model.stickies.map((sticky) => [sticky.id, sticky]))
  return model.links
    .map((link) => ({ event: byId.get(link.from), policy: byId.get(link.to) }))
    .filter(
      (pair): pair is { event: Sticky; policy: Sticky } =>
        pair.event?.type === 'event' &&
        pair.event.context === model.context &&
        pair.policy?.type === 'policy' &&
        pair.policy.context !== model.context,
    )
    .map(({ event, policy }) => ({ from: model.context, event, to: policy.context, policy }))
    .sort((a, b) => `${a.event.label}|${a.to}`.localeCompare(`${b.event.label}|${b.to}`, 'en'))
}

function askedBy(model: Model): Asked[] {
  return model.stickies
    .filter((sticky) => sticky.type === 'externalSystem' && sticky.context === model.context)
    .flatMap((port) =>
      port.answers.flatMap((service) =>
        service.context === null || service.slug === null
          ? []
          : [
              {
                from: model.context,
                port,
                to: service.context,
                service: { slug: service.slug, label: service.label },
              },
            ],
      ),
    )
    .sort((a, b) => `${a.port.label}|${a.to}`.localeCompare(`${b.port.label}|${b.to}`, 'en'))
}

function contextOf(model: Model): string {
  const count = (type: Sticky['type']): number =>
    model.stickies.filter((sticky) => sticky.type === type && sticky.context === model.context).length
  const lanes = model.modules.length === 0 ? 1 : model.modules.length
  return `<li><a class="wall" href="${folderOf(model.context)}/">${escaped(sentenceOf(model.context))}</a><span class="counts">${lanes} ${lanes === 1 ? 'lane' : 'lanes'} · ${count('command')} commands · ${count('readModel')} read models · ${count('event')} events · ${count('policy')} policies</span></li>`
}

function publishedRow(relation: Published): string[] {
  return [
    escaped(sentenceOf(relation.from)),
    linkTo(relation.from, relation.event.slug, relation.event.label),
    escaped(sentenceOf(relation.to)),
    linkTo(relation.to, relation.policy.slug.replace(/^[^/]*\//, ''), relation.policy.label),
  ]
}

function askedRow(relation: Asked): string[] {
  return [
    escaped(sentenceOf(relation.from)),
    linkTo(relation.from, relation.port.slug, relation.port.label),
    escaped(sentenceOf(relation.to)),
    linkTo(relation.to, relation.service.slug, relation.service.label),
  ]
}

function linkTo(context: string, slug: string, label: string): string {
  return `<a href="${folderOf(context)}/#at=${encodeURIComponent(slug)}">${escaped(label)}</a>`
}

function tableOf(head: string[], rows: string[][]): string {
  return `<table><thead><tr>${head.map((cell) => `<th>${cell}</th>`).join('')}</tr></thead><tbody>${rows.map((row) => `<tr>${row.map((cell) => `<td>${cell}</td>`).join('')}</tr>`).join('')}</tbody></table>`
}

/** The contexts on a circle, and one arrow per pair of contexts and per kind of relation, its count on hover. */
function mapOf(models: Model[], published: Published[], asked: Asked[]): string {
  const centre = { x: MAP.width / 2, y: MAP.height / 2 }
  const places = new Map(
    models.map((model, index) => {
      const angle = -Math.PI / 2 + (2 * Math.PI * index) / models.length
      return [
        model.context,
        { x: centre.x + MAP.radius * 1.35 * Math.cos(angle), y: centre.y + MAP.radius * Math.sin(angle) },
      ]
    }),
  )
  const arrows = [
    ...pairsOf(
      published.map((relation) => [relation.from, relation.to]),
      'events',
      'publishes to',
    ),
    ...pairsOf(
      asked.map((relation) => [relation.from, relation.to]),
      'services',
      'asks',
    ),
  ]
    .map((pair) => arrowOf(pair, places))
    .join('')
  const boxes = models
    .map((model) => {
      const at = places.get(model.context) ?? centre
      const { w, h } = MAP.box
      return `<a class="context" href="${folderOf(model.context)}/"><rect x="${round(at.x - w / 2)}" y="${round(at.y - h / 2)}" width="${w}" height="${h}" rx="3"/><text x="${round(at.x)}" y="${round(at.y + 7)}">${escaped(sentenceOf(model.context))}</text></a>`
    })
    .join('')
  return `<figure id="map"><svg viewBox="0 0 ${MAP.width} ${MAP.height}" role="img" aria-label="The contexts and what goes from one to another"><defs>${markerOf('events')}${markerOf('services')}</defs>${arrows}${boxes}</svg><figcaption><span class="key events">publishes events to</span> <span class="key services">asks a published service of</span></figcaption></figure>`
}

interface Pair {
  from: string
  to: string
  kind: 'events' | 'services'
  verb: string
  count: number
}

function pairsOf(relations: string[][], kind: Pair['kind'], verb: string): Pair[] {
  const counts = new Map<string, number>()
  for (const [from, to] of relations) counts.set(`${from}>${to}`, (counts.get(`${from}>${to}`) ?? 0) + 1)
  return [...counts].map(([key, count]) => {
    const [from = '', to = ''] = key.split('>')
    return { from, to, kind, verb, count }
  })
}

/** An arrow between the edges of two boxes, bowed to its left so that the arrows of both ways part. */
function arrowOf(pair: Pair, places: Map<string, { x: number; y: number }>): string {
  const from = places.get(pair.from)
  const to = places.get(pair.to)
  if (from === undefined || to === undefined) return ''
  const start = edgeOf(from, to)
  const end = edgeOf(to, from)
  const bow = {
    x: (start.x + end.x) / 2 + (end.y - start.y) * 0.12,
    y: (start.y + end.y) / 2 - (end.x - start.x) * 0.12,
  }
  const tip = `${sentenceOf(pair.from)} ${pair.verb} ${sentenceOf(pair.to)}: ${pair.count}`
  return `<path class="relation ${pair.kind}" d="M${round(start.x)} ${round(start.y)}Q${round(bow.x)} ${round(bow.y)} ${round(end.x)} ${round(end.y)}" marker-end="url(#head-${pair.kind})"><title>${escaped(tip)}</title></path>`
}

/** Where the line from a box's centre towards another point leaves the box. */
function edgeOf(from: { x: number; y: number }, towards: { x: number; y: number }): { x: number; y: number } {
  const dx = towards.x - from.x
  const dy = towards.y - from.y
  const scale = Math.min(
    dx === 0 ? Number.POSITIVE_INFINITY : (MAP.box.w / 2 + 6) / Math.abs(dx),
    dy === 0 ? Number.POSITIVE_INFINITY : (MAP.box.h / 2 + 6) / Math.abs(dy),
  )
  return { x: from.x + dx * scale, y: from.y + dy * scale }
}

function markerOf(kind: Pair['kind']): string {
  return `<marker id="head-${kind}" class="head ${kind}" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z"/></marker>`
}

function round(value: number): number {
  return Math.round(value * 10) / 10
}

const STYLE = `
:root { color-scheme: light; --paper: #fbf9f3; --ink: #1b2030; --soft: #5e6270; --line: #e6e1d4; --event: #e07d1f; --service: #c2457a; --box: #fff4ad; --link: #1f5fbf; }
@media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) { color-scheme: dark; --paper: #1c2026; --ink: #ece8dc; --soft: #a5a9b4; --line: #343a44; --event: #ffa348; --service: #ff7fb0; --box: #4a4322; --link: #8ab8ff; } }
:root[data-theme="dark"] { color-scheme: dark; --paper: #1c2026; --ink: #ece8dc; --soft: #a5a9b4; --line: #343a44; --event: #ffa348; --service: #ff7fb0; --box: #4a4322; --link: #8ab8ff; }
* { box-sizing: border-box; }
body { margin: 0; background: var(--paper); color: var(--ink); font: 16px/1.5 system-ui, sans-serif; }
main { max-width: 1000px; margin: 0 auto; padding: 24px 16px 64px; }
h1, h2 { font-family: "Wall Hand", cursive; font-weight: normal; }
h1 { font-size: 40px; margin: 0 0 8px; }
h2 { font-size: 28px; margin: 40px 0 12px; }
.lead { color: var(--soft); max-width: 70ch; }
a { color: var(--link); }
figure { margin: 24px 0 0; }
svg { width: 100%; height: auto; display: block; }
.context rect { fill: var(--box); stroke: var(--ink); stroke-width: 1.5; }
.context text { font: 24px "Wall Hand", cursive; fill: var(--ink); text-anchor: middle; }
.context:hover rect, .context:focus-visible rect { stroke-width: 3; }
.relation { fill: none; stroke-width: 2; }
.relation.events { stroke: var(--event); }
.relation.services { stroke: var(--service); stroke-dasharray: 6 5; }
.head.events path { fill: var(--event); }
.head.services path { fill: var(--service); }
figcaption { color: var(--soft); font-size: 14px; display: flex; gap: 24px; flex-wrap: wrap; }
.key::before { content: ""; display: inline-block; width: 28px; margin-right: 8px; vertical-align: middle; border-top: 2px solid var(--event); }
.key.services::before { border-top: 2px dashed var(--service); }
ul { list-style: none; padding: 0; display: grid; gap: 8px; }
li { display: flex; flex-wrap: wrap; gap: 4px 16px; align-items: baseline; }
.wall { font: 24px "Wall Hand", cursive; }
.counts { color: var(--soft); font-size: 14px; }
table { border-collapse: collapse; width: 100%; font-size: 14px; display: block; overflow-x: auto; }
th, td { text-align: left; padding: 6px 12px 6px 0; border-bottom: 1px solid var(--line); white-space: nowrap; }
th { color: var(--soft); font-weight: 600; }
`
