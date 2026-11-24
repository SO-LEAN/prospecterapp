import type { LaidOutView, PlacedNode, RoutedEdge } from './layout.ts'
import type { ViewNode } from './views.ts'

type Kind = ViewNode['type']

/** The post-its of the event storming, in the order a reader meets them, and their names in the legend. */
const KINDS: [Kind, string][] = [
  ['actor', 'Acteur'],
  ['command', 'Commande'],
  ['aggregate', 'Agrégat'],
  ['event', 'Événement'],
  ['policy', 'Réaction'],
  ['readModel', 'Modèle de lecture'],
  ['externalSystem', 'Système externe'],
  ['module', 'Module'],
]

/** A post-it holds four lines of its label, of some twenty characters each. */
const CHARACTERS_BY_LINE = 20
const LINES = 4
const LINE_HEIGHT = 17
/** A caption longer than a line is narrowed to fit the post-it, within its margins. */
const CAPTION_CHARACTERS = 26
const CAPTION_MARGIN = 8

/**
 * The page of a context's event storming: one board per view, a filter by type of post-it, and on hover the
 * arrows of a post-it lit. It stands alone: it loads no outside script or stylesheet, and needs no build of the SPA.
 */
export function pageOf(context: string, views: LaidOutView[]): string {
  return `<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Event storming · ${escaped(context)}</title>
<style>${STYLE}</style>
</head>
<body>
<header>
<h1>Event storming <span>${escaped(context)}</span></h1>
<p>Généré depuis le code : chaque commande porte le titre de la feature de son cas d'usage. Une flèche en pointillé ne se produit que sous condition. Les post-its estompés viennent d'un autre module ou d'un autre contexte.</p>
<nav aria-label="Vues">${views.map((laidOut, index) => tabOf(laidOut, index === 0)).join('')}</nav>
<fieldset><legend>Types</legend>${KINDS.map(([kind, name]) => filterOf(kind, name)).join('')}</fieldset>
</header>
<main>${views.map((laidOut, index) => boardOf(laidOut, index === 0)).join('\n')}</main>
<script>${SCRIPT}</script>
</body>
</html>
`
}

function tabOf(laidOut: LaidOutView, current: boolean): string {
  return `<button type="button" data-view="${escaped(laidOut.view.id)}" aria-pressed="${current}">${escaped(laidOut.view.title)}</button>`
}

function filterOf(kind: Kind, name: string): string {
  return `<label><input type="checkbox" data-kind="${kind}" checked><span class="swatch type-${kind}"></span>${name}</label>`
}

function boardOf(laidOut: LaidOutView, current: boolean): string {
  const { view, width, height } = laidOut
  const kindOf = new Map(laidOut.nodes.map((placed) => [placed.node.key, placed.node.type]))
  return `<section class="board" data-view="${escaped(view.id)}"${current ? '' : ' hidden'}>
<h2>${escaped(view.title)}</h2>
<div class="scroll"><svg viewBox="0 0 ${width} ${height}" width="${width}" height="${height}" role="img" aria-label="${escaped(view.title)}">
<defs><marker id="arrow-${escaped(view.id)}" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z"/></marker></defs>
${laidOut.edges.map((routed) => arrowOf(routed, view.id, kindOf)).join('\n')}
${laidOut.nodes.map(stickyOf).join('\n')}
</svg></div>
</section>`
}

function arrowOf(routed: RoutedEdge, viewId: string, kindOf: Map<string, Kind>): string {
  const { edge, points } = routed
  const path = points
    .map((point, index) => `${index === 0 ? 'M' : 'L'}${tenths(point.x)},${tenths(point.y)}`)
    .join(' ')
  const classes = ['link', ...(edge.certain ? [] : ['uncertain'])].join(' ')
  return `<path class="${classes}" d="${path}" data-from="${escaped(edge.from)}" data-to="${escaped(edge.to)}" data-from-kind="${kindOf.get(edge.from)}" data-to-kind="${kindOf.get(edge.to)}" marker-end="url(#arrow-${escaped(viewId)})"/>`
}

function stickyOf(placed: PlacedNode): string {
  const { node, x, y, width, height } = placed
  const classes = ['sticky', `type-${node.type}`, ...(node.faded ? ['faded'] : [])].join(' ')
  const lines = linesOf(node.label)
  const top = (height - (lines.length + 1) * LINE_HEIGHT) / 2 + LINE_HEIGHT
  const caption =
    node.name === node.label
      ? (node.detail ?? KINDS.find(([kind]) => kind === node.type)?.[1] ?? '')
      : node.name
  return `<g class="${classes}" data-key="${escaped(node.key)}" transform="translate(${tenths(x)},${tenths(y)})" tabindex="0"><title>${escaped(node.hover)}</title>
<rect width="${width}" height="${height}" rx="3"/>
${lines.map((line, index) => `<text class="label" x="${width / 2}" y="${top + index * LINE_HEIGHT}">${escaped(line)}</text>`).join('')}
<text class="caption" x="${width / 2}" y="${top + lines.length * LINE_HEIGHT + 4}"${caption.length > CAPTION_CHARACTERS ? ` textLength="${width - 2 * CAPTION_MARGIN}" lengthAdjust="spacingAndGlyphs"` : ''}>${escaped(caption)}</text></g>`
}

/** Cuts a label into lines at its spaces, or inside a long name, and ends it with an ellipsis past the fourth. */
function linesOf(label: string): string[] {
  const lines: string[] = []
  for (const { text, glued } of piecesOf(label)) {
    const last = lines.at(-1)
    const joined = `${last}${glued ? '' : ' '}${text}`
    if (last !== undefined && joined.length <= CHARACTERS_BY_LINE) lines[lines.length - 1] = joined
    else lines.push(text)
  }
  return lines.length <= LINES ? lines : [...lines.slice(0, LINES - 1), `${lines[LINES - 1]}…`]
}

/** The words of a label, a word too long for a line cut into the parts of its name, glued to the one before. */
function piecesOf(label: string): { text: string; glued: boolean }[] {
  return label
    .split(/\s+/)
    .flatMap((word) =>
      word.length <= CHARACTERS_BY_LINE
        ? [{ text: word, glued: false }]
        : word.split(/(?<=[a-z])(?=[A-Z])/).map((part, index) => ({ text: part, glued: index > 0 })),
    )
}

/** ELK places to a fraction of a point: a tenth is enough to draw, and reads the same from one generation to the next. */
function tenths(coordinate: number): number {
  return Math.round(coordinate * 10) / 10
}

function escaped(text: string): string {
  return text
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
}

const STYLE = `
:root {
  color-scheme: light dark;
  --paper: #f6f5f1;
  --ink: #23262b;
  --muted: #5d6470;
  --line: #59606b;
  --panel: #ffffff;
  --border: #d9d7d0;
  --accent: #2f6fde;
  --actor: #ffe27a;
  --command: #8cc8f5;
  --aggregate: #fff3b0;
  --event: #ffad5c;
  --policy: #d2b4f0;
  --readModel: #b2e09c;
  --externalSystem: #f6b3cb;
  --module: #e7e4dc;
}
@media (prefers-color-scheme: dark) {
  :root {
    --paper: #17191d;
    --ink: #e8e6e1;
    --muted: #a3a9b3;
    --line: #9aa2ad;
    --panel: #22252b;
    --border: #3a3e46;
    --accent: #7aa7ff;
    --module: #3a3e46;
  }
}
* { box-sizing: border-box; }
body { margin: 0; background: var(--paper); color: var(--ink); font: 15px/1.45 system-ui, -apple-system, "Segoe UI", sans-serif; }
header { padding: 20px 16px 12px; max-width: 1200px; }
h1 { margin: 0 0 6px; font-size: 22px; font-weight: 650; }
h1 span { color: var(--accent); }
header p { margin: 0 0 14px; color: var(--muted); max-width: 72ch; }
nav { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
nav button { font: inherit; padding: 6px 12px; border-radius: 999px; border: 1px solid var(--border); background: var(--panel); color: var(--ink); cursor: pointer; }
nav button[aria-pressed="true"] { background: var(--accent); border-color: var(--accent); color: var(--paper); }
fieldset { display: flex; flex-wrap: wrap; gap: 6px 16px; margin: 0; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; background: var(--panel); }
legend { padding: 0 4px; color: var(--muted); font-size: 13px; }
fieldset label { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; cursor: pointer; }
.swatch { width: 14px; height: 14px; border-radius: 2px; border: 1px solid rgb(0 0 0 / 0.2); }
.board { padding: 0 16px 24px; }
.board h2 { font-size: 16px; font-weight: 600; margin: 8px 0; }
.scroll { overflow: auto; border: 1px solid var(--border); border-radius: 8px; background: var(--panel); }
svg { display: block; max-width: none; }
.link { fill: none; stroke: var(--line); stroke-width: 1.5; transition: opacity 0.15s; }
.link.uncertain { stroke-dasharray: 6 5; }
marker path { fill: var(--line); }
.sticky rect { stroke: rgb(0 0 0 / 0.18); filter: drop-shadow(0 1px 1.5px rgb(0 0 0 / 0.22)); }
.sticky text { text-anchor: middle; fill: #1f2328; }
.sticky .label { font-size: 14px; font-weight: 560; }
.sticky .caption { font-size: 11px; fill: #4a4f57; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
.type-module text { fill: var(--ink); }
.type-module .caption { fill: var(--muted); }
.sticky { cursor: default; outline: none; }
.sticky:focus-visible rect { stroke: var(--accent); stroke-width: 3; }
${KINDS.map(([kind]) => `.type-${kind} rect, .swatch.type-${kind} { fill: var(--${kind}); background: var(--${kind}); }`).join('\n')}
${KINDS.map(([kind]) => `.type-${kind}.faded rect { fill: var(--panel); stroke: var(--${kind}); stroke-width: 2.5; stroke-dasharray: 6 4; filter: none; }`).join('\n')}
.sticky.faded text { fill: var(--muted); }
.focusing .link:not(.lit) { opacity: 0.12; }
.focusing .link.lit { stroke: var(--accent); stroke-width: 2.5; }
${KINDS.map(([kind]) => `.without-${kind} .type-${kind}:not(.swatch), .without-${kind} [data-from-kind="${kind}"], .without-${kind} [data-to-kind="${kind}"] { display: none; }`).join('\n')}
`

const SCRIPT = `
const boards = document.querySelectorAll('.board')
const tabs = document.querySelectorAll('nav button')
function show(id) {
  if (![...boards].some((board) => board.dataset.view === id)) return
  for (const board of boards) board.hidden = board.dataset.view !== id
  for (const tab of tabs) tab.setAttribute('aria-pressed', String(tab.dataset.view === id))
}
for (const tab of tabs) tab.addEventListener('click', () => { show(tab.dataset.view); history.replaceState(null, '', '#' + tab.dataset.view) })
show(decodeURIComponent(location.hash.slice(1)))
for (const filter of document.querySelectorAll('fieldset input')) {
  filter.addEventListener('change', () => document.body.classList.toggle('without-' + filter.dataset.kind, !filter.checked))
}
for (const sticky of document.querySelectorAll('.sticky')) {
  const light = (on) => {
    document.body.classList.toggle('focusing', on)
    for (const link of sticky.closest('svg').querySelectorAll('.link')) {
      link.classList.toggle('lit', on && (link.dataset.from === sticky.dataset.key || link.dataset.to === sticky.dataset.key))
    }
  }
  for (const [event, on] of [['mouseenter', true], ['mouseleave', false], ['focus', true], ['blur', false]]) sticky.addEventListener(event, () => light(on))
}
`
