import { type Grid, gridOf } from './grid.ts'
import { escaped } from './html.ts'
import { type Letters, lettersOf } from './lettering.ts'
import { arrowOf, braceOf, hashOf, lean, wobbly } from './marker.ts'
import type { Model } from './model.ts'
import { dataOf, type WallData } from './page-data.ts'
import { type Pictogram, pictogramsOf } from './pictograms.ts'
import { KIND_NAMES, sentenceAbout } from './story.ts'
import { type Jump, type Kind, type Note, sentenceOf, type Wall, wallOf } from './wall.ts'
import { runTheWall } from './wall-script.ts'

/** What the page holds rather than loads: its style sheet and its handwriting, a woff2 file in base 64. */
export interface Assets {
  style: string
  font: string
}

/** The kinds of the legend, in the order of the grammar. */
const LEGEND: Kind[] = [
  'actor',
  'command',
  'aggregate',
  'event',
  'policy',
  'readModel',
  'externalSystem',
  'hotspot',
]

/** The stick figures of the actors: a person, the member's AI with its square head, anyone with a second person. */
export const FIGURES: Record<string, string> = {
  default: '<circle cx="15" cy="6" r="4"/><path d="M15 10v10M7 14.5h16M15 20l-6 8M15 20l6 8"/>',
  mcp: '<rect x="10" y="3" width="10" height="8" rx="2"/><path d="M15 3V.8M13 7h.1M17 7h.1M15 11v9M7 15h16M15 20l-6 8M15 20l6 8"/>',
  public:
    '<circle cx="11" cy="6" r="3.6"/><path d="M11 9.6v10M4.5 14h13M11 19.6l-5 8M11 19.6l5 8"/><circle cx="22" cy="9" r="2.8" opacity=".55"/><path d="M22 12v7.5M18.5 15h8M22 19.5l-3.5 7M22 19.5l3.5 7" opacity=".55"/>',
}

const ICONS = {
  on: '<svg viewBox="0 0 24 24"><path d="M5 12h13M13 6l6 6-6 6"/></svg>',
  back: '<svg viewBox="0 0 24 24"><path d="M19 12H6M11 6l-6 6 6 6"/></svg>',
  away: '<svg viewBox="0 0 24 24"><path d="M8 16L18 6M10 6h8v8"/></svg>',
  close: '<svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>',
  fold: '<svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>',
}

/**
 * The page of a context's wall. It stands alone: it loads no outside script, stylesheet or font, and needs no build
 * of the SPA. The post-its stand where the generator laid them out; the script moves them when a lane folds or the
 * rows get compact, and moves the camera.
 */
export function pageOf(model: Model, assets: Assets): string {
  const wall = wallOf(model)
  const grids = { roomy: gridOf(wall, 'roomy'), compact: gridOf(wall, 'compact') }
  const data = dataOf(wall, grids)
  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Event storming · ${escaped(data.label)}</title>
<script>${THEME}</script>
<style>
/* Patrick Hand, (c) Patrick Wagesreiter, SIL Open Font License 1.1, embedded so that the page loads nothing. */
@font-face { font-family: "Wall Hand"; src: url(data:font/woff2;base64,${assets.font}) format("woff2"); font-display: block; }
${assets.style}
</style>
</head>
<body>
<div id="stage" aria-label="The wall">
<div id="world"><div id="roll" style="width:${grids.roomy.width}px;height:${grids.roomy.height}px">
<span class="corner-tape" style="left:-34px;top:-12px;transform:rotate(-38deg)"></span><span class="corner-tape" style="right:-34px;top:-12px;transform:rotate(38deg)"></span>
<div class="wall-title">${escaped(data.label)}<small>event storming, read from the code</small></div>
<div id="ink"></div>
${stripsOf(data)}
${wall.notes.map((note, index) => stickyOf(wall, grids.roomy, note, index)).join('\n')}
${tapesOf(data)}
<div id="labels"></div>
<div id="hits"></div>
</div></div>
</div>
${chromeOf(wall, data)}
<script>
${[hashOf, lean, wobbly, arrowOf, braceOf].map(String).join('\n')}
(${String(runTheWall)})(${JSON.stringify(data).replaceAll('</', '<\\/')})
</script>
</body>
</html>
`
}

/** The theme is chosen before the first paint: ?theme=dark|light, else the saved choice, else the system's. */
const THEME = `(function () {
  var asked = new URLSearchParams(location.search).get('theme'), saved = null
  try { saved = localStorage.getItem('es-theme') } catch (e) {}
  var theme = asked || saved || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
  document.documentElement.dataset.theme = theme === 'dark' ? 'dark' : 'light'
})()`

function stripsOf(data: WallData): string {
  return data.strips
    .map((strip, index) => {
      const at = strip.places.roomy
      return `<div class="strip" data-strip="${index}" style="left:${at.x}px;top:${at.y}px;width:${at.w}px;height:${at.h}px;--r:${strip.tilt.toFixed(2)}deg"><b>${escaped(strip.context)}</b></div>`
    })
    .join('')
}

function stickyOf(wall: Wall, grid: Grid, note: Note, index: number): string {
  const at = grid.places.get(note) ?? { x: 0, y: 0, w: note.w, h: note.h }
  const classes = [
    'sticky',
    `k-${note.kind}`,
    ...(note.port ? ['port'] : []),
    ...(note.badge ? ['badge'] : []),
  ].join(' ')
  const tip = tipOf(note)
  const style = `left:${tenth(at.x)}px;top:${tenth(at.y)}px;width:${at.w}px;height:${at.h}px;z-index:${note.z};--r:${note.tilt.toFixed(2)}deg`
  return `<div class="${classes}" data-node="${index}" role="button" tabindex="${index === 0 ? 0 : -1}" aria-label="${escaped(ariaOf(wall, note))}"${tip === null ? '' : ` data-tip="${escaped(tip)}"`} style="${style}"><div class="paper">${figureOf(note)}${lettersOf(note).map(spanOf).join('')}</div>${jumpsOf(note)}</div>`
}

function tenth(value: number): number {
  return Math.round(value * 10) / 10
}

function figureOf(note: Note): string {
  if (note.kind !== 'actor') return ''
  return `<svg class="figure" viewBox="0 0 30 30" aria-hidden="true">${FIGURES[note.sticky?.name ?? ''] ?? FIGURES.default}</svg>`
}

function spanOf(letters: Letters): string {
  const style = `left:${tenth(letters.x)}px;top:${tenth(letters.y)}px;width:${tenth(letters.w) + 1}px;font-size:${letters.size}px;line-height:${letters.lineHeight}`
  return `<p class="t role-${letters.role} font-${letters.font}${letters.align === 'center' ? ' center' : ''}" style="${style}">${letters.lines.map(escaped).join('<br>')}</p>`
}

function tipOf(note: Note): string | null {
  if (note.badge) return `${note.label} can call this through its tool, without a screen`
  if (note.port) return `${note.label}: asks ${(note.sticky?.answeredBy ?? []).map(sentenceOf).join(', ')}`
  return null
}

function jumpsOf(note: Note): string {
  const { shown, more } = pictogramsOf(note)
  return [...shown.map(pictogramOf), ...(more === null ? [] : [moreOf(more.offset, more.hidden)])].join('')
}

function pictogramOf({ jump, index, side, offset }: Pictogram): string {
  const attributes = `style="${side}:${offset}px" data-jump="${index}" data-tip="${escaped(jump.tip)}" aria-label="${escaped(jump.tip)}" tabindex="-1"`
  return jumpOf(jump, ['jump'], attributes, iconOf(jump))
}

/** A link to another wall, or a button to a post-it of this wall; both point away when they lead to no post-it. */
function jumpOf(jump: Jump, classes: string[], attributes: string, content: string): string {
  const away = jump.to === null ? ['away'] : []
  const kept = `class="${[...classes, ...away].join(' ')}" ${attributes}`
  if (jump.href !== null) return `<a href="${escaped(jump.href)}" ${kept}>${content}</a>`
  return `<button ${kept}>${content}</button>`
}

function iconOf(jump: Jump): string {
  if (jump.to === null) return ICONS.away
  return jump.back ? ICONS.back : ICONS.on
}

/** The « +n » in the last place, whose hover or focus lists the pictograms left out, each by what it leads to. */
function moreOf(offset: number, hidden: Pictogram[]): string {
  const label = `${hidden.length} more: ${hidden.map(({ jump }) => jump.tip).join(', ')}`
  const items = hidden.map(({ jump, index }) =>
    jumpOf(
      jump,
      [],
      `data-jump="${index}" tabindex="-1"`,
      `${iconOf(jump)}<span>${escaped(jump.tip)}</span>`,
    ),
  )
  return `<span class="more" style="right:${offset}px"><button class="jump more" aria-label="${escaped(label)}" tabindex="-1">+${hidden.length}</button><span class="more-jumps">${items.join('')}</span></span>`
}

/** A post-it's accessible name: what it is, where it stands, what comes before and after it. */
function ariaOf(wall: Wall, note: Note): string {
  const before = wall.edges.filter((edge) => edge.to === note).map((edge) => edge.from.label)
  const after = wall.edges.filter((edge) => edge.from === note).map((edge) => edge.to.label)
  const where =
    note.foreign && note.sticky !== null
      ? `on ${sentenceOf(note.sticky.context)}'s wall`
      : `module ${sentenceOf(note.step.lane)}`
  return [
    `${KIND_NAMES[note.kind]}: ${note.label}`,
    where,
    before.length > 0 ? `after ${before.join(', ')}` : '',
    after.length > 0 ? `then ${after.join(', ')}` : '',
  ]
    .filter((part) => part !== '')
    .join('. ')
}

function tapesOf(data: WallData): string {
  return data.lanes
    .map((lane, index) => {
      const top = lane.top.roomy
      const count = `${lane.useCases} use case${lane.useCases > 1 ? 's' : ''}`
      return [
        `<button class="lane-tape" style="top:${top + 22}px" data-lane="${index}" data-tip="Step up to the ${escaped(lane.label)} module">${escaped(lane.label)}</button>`,
        `<button class="lane-fold" style="top:${top + 74}px" data-fold="${index}" aria-expanded="true" data-tip="Fold this lane to the names of its steps" aria-label="Fold or unfold the ${escaped(lane.label)} lane">${ICONS.fold}</button>`,
        `<span class="lane-count" style="top:${top + 76}px" data-count="${index}">${count}</span>`,
      ].join('')
    })
    .join('')
}

function chromeOf(wall: Wall, data: WallData): string {
  return `<header id="bar" class="card" role="toolbar" aria-label="Wall navigation">
<button class="btn icon" id="back" data-tip="Back to where you were" aria-label="Back" disabled><svg viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg></button>
<a class="crumb wide" href="../" data-tip="Every context, and what goes from one to another">Event storming</a><span class="crumb wide">›</span><strong class="crumb">${escaped(data.label)}</strong><span class="crumb">›</span>
<div class="seg" id="lanes" role="group" aria-label="Modules"><button aria-pressed="true" data-lane="">Whole wall</button>${data.lanes.map((lane, index) => `<button aria-pressed="false" data-lane="${index}">${escaped(lane.label)}</button>`).join('')}</div>
<span class="grow"></span>
<div class="search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/></svg><input id="q" type="search" placeholder="Find a post-it…" aria-label="Find a post-it" autocomplete="off" spellcheck="false"><kbd>/</kbd><div id="results" class="card" role="listbox"></div></div>
<span class="sep"></span>
<button class="btn" id="storyBtn" data-tip="The same wall, told in sentences (T)"><svg viewBox="0 0 24 24"><path d="M5 6h14M5 12h14M5 18h9"/></svg>Read as text</button>
<button class="btn icon" id="themeBtn" data-tip="Lights on, lights off (D)" aria-label="Switch between light and dark"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5L19 19M5 19l1.5-1.5M17.5 6.5L19 5"/></svg></button>
<button class="btn icon" id="helpBtn" data-tip="Keyboard and mouse (?)" aria-label="Help"><svg viewBox="0 0 24 24"><path d="M9.5 9a2.6 2.6 0 1 1 3.6 2.4c-.8.4-1.1 1-1.1 1.9M12 17.2v.3"/><circle cx="12" cy="12" r="9.5"/></svg></button>
</header>
<aside id="legend" class="card" aria-label="How to read the wall">
<h2 data-tip="Time runs left to right. Post-its that touch are one step: who asks, the command, what it changes, what happened. Marker arrows are reactions. Kraft paper is another context's wall.">How to read the wall <button class="btn icon" id="legendFold" aria-label="Fold the legend" data-tip="Fold (L)">${ICONS.fold}</button></h2>
<div class="grammar" id="kinds">${LEGEND.map((kind) => `<button class="chip" aria-pressed="true" data-kind="${kind}" data-tip="Show or hide the ${KIND_NAMES[kind].toLowerCase()}s"><span class="dot" style="--c:var(--${kind})"></span>${KIND_NAMES[kind]}</button>`).join('')}</div>
<div class="grammar legend-more"><span class="grammar" id="who">${data.actors.map((index) => whoOf(data, index)).join('')}</span>
<span class="row"><svg width="44" height="10"><path d="M2 5h34M31 1l6 4-6 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>always<svg class="sometimes" width="44" height="10"><path d="M2 5h34" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-dasharray="2 7"/><path d="M31 1l6 4-6 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>only sometimes</span></div>
</aside>
<aside id="dock" class="card" aria-label="Map and zoom">
<div id="mini" aria-hidden="true"></div>
<div class="zoom">
<button class="btn icon" id="zoomOut" aria-label="Zoom out" data-tip="Step back (−)"><svg viewBox="0 0 24 24"><path d="M6 12h12"/></svg></button>
<output id="zoomPct">100%</output>
<button class="btn icon" id="zoomIn" aria-label="Zoom in" data-tip="Step closer (+)"><svg viewBox="0 0 24 24"><path d="M6 12h12M12 6v12"/></svg></button>
<button class="btn" id="dense" aria-pressed="false" data-tip="Compact rows: what a use case asks becomes pink squares (C)">Compact</button>
<button class="btn" id="fit" data-tip="See the whole wall (F)">Whole wall</button>
</div>
</aside>
<aside id="inspector" class="card" aria-label="The selected post-it" hidden></aside>
${textOf(wall, data)}
<section id="help" class="card" role="dialog" aria-label="Keyboard and mouse" hidden>
<h2>Keyboard and mouse <button class="btn icon" data-close="help" aria-label="Close">${ICONS.close}</button></h2>
<table>
<tr><td>Drag, or scroll</td><td>Walk along the wall</td></tr>
<tr><td><kbd>Ctrl</kbd> + scroll, <kbd>+</kbd> <kbd>−</kbd></td><td>Step closer or back</td></tr>
<tr><td><kbd>F</kbd> &nbsp; <kbd>1</kbd></td><td>Whole wall &nbsp;·&nbsp; real size</td></tr>
<tr><td><kbd>/</kbd></td><td>Find a post-it</td></tr>
<tr><td><kbd>Tab</kbd> then the arrows</td><td>Move from post-it to post-it</td></tr>
<tr><td><kbd>Enter</kbd></td><td>Pin the story of a post-it, open its card</td></tr>
<tr><td><kbd>J</kbd></td><td>Follow the post-it's jump pictogram</td></tr>
<tr><td><kbd>T</kbd> &nbsp; <kbd>L</kbd> &nbsp; <kbd>D</kbd> &nbsp; <kbd>C</kbd></td><td>Text &nbsp;·&nbsp; legend &nbsp;·&nbsp; lights &nbsp;·&nbsp; compact rows</td></tr>
<tr><td><kbd>Esc</kbd></td><td>Let go</td></tr>
</table>
</section>
<div id="tip" role="tooltip"></div>`
}

function whoOf(data: WallData, index: number): string {
  const note = data.notes[index]
  if (note === undefined) return ''
  const figure = FIGURES[note.slug.split(':')[1] ?? ''] ?? FIGURES.default
  return `<button class="chip who" aria-pressed="false" data-who="${index}" data-tip="Light everything ${escaped(note.label)} can do"><svg viewBox="0 0 30 30">${figure}</svg>${escaped(note.label)}</button>`
}

/** The wall told in sentences, lane by lane: it reads without the script, and prints. */
function textOf(wall: Wall, data: WallData): string {
  const lanes = wall.lanes.map((lane) => {
    const sentences = lane.steps
      .filter((step) => step.kind !== 'actor')
      .map((step) => `<li>${sentenceAbout(wall, step)}</li>`)
    return `<h3>${escaped(lane.label)}</h3><ol>${sentences.join('')}</ol>`
  })
  return `<section id="story" class="card" aria-label="The wall as text" hidden><h2>${escaped(data.label)}, told in sentences <button class="btn icon" data-close="story" aria-label="Close">${ICONS.close}</button></h2>${lanes.join('')}</section>`
}
