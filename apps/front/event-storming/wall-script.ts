import type { Place } from './grid.ts'
import { arrowOf, braceOf, type Placed, wobbly } from './marker.ts'
import type { NoteData, WallData } from './page-data.ts'

/**
 * The page's behaviour in the browser: the camera along the wall, the minimap, a folded lane, compact rows, a story
 * lit and pinned with its card, the history of the jumps, the search, the legend as a filter, the conditions of the
 * dashed lines, and the lights. The generator laid every post-it out; this script only moves them.
 *
 * The page writes this function's source in its script, with those of the marker's functions, so it calls nothing
 * else and holds its helpers.
 */
export function runTheWall(data: WallData): void {
  const element = (id: string): HTMLElement => {
    const found = document.getElementById(id)
    if (found === null) throw new Error(`The page has no #${id}`)
    return found
  }
  const world = element('world')
  const roll = element('roll')
  const stage = element('stage')
  const ink = element('ink')
  const tip = element('tip')
  const search = element('q') as HTMLInputElement
  const results = element('results')
  const inspector = element('inspector')
  const story = element('story')
  const back = element('back') as HTMLButtonElement
  const notes = data.notes
  const stickies = notes.map((_, index) => roll.querySelector<HTMLElement>(`[data-node="${index}"]`))
  const strips = data.strips.map((_, index) => roll.querySelector<HTMLElement>(`[data-strip="${index}"]`))
  const lanes = data.lanes.map((_, index) => ({
    tape: roll.querySelector<HTMLElement>(`[data-lane="${index}"]`),
    fold: roll.querySelector<HTMLElement>(`[data-fold="${index}"]`),
    count: roll.querySelector<HTMLElement>(`[data-count="${index}"]`),
  }))
  const hash = (): URLSearchParams => new URLSearchParams(location.hash.slice(1))
  const folded = new Set<number>()
  let density: 'roomy' | 'compact' = hash().get('rows') === 'compact' ? 'compact' : 'roomy'
  let wall = { w: 0, h: 0 }
  let pinned: number | null = null
  let depth = 0

  /* ───── Where each post-it stands, given the folded lanes and the rows' density ───── */
  const laneTop = (lane: number): number =>
    data.top + data.lanes.slice(0, lane).reduce((top, other, index) => top + heightOf(other, index), 0)
  function heightOf(lane: WallData['lanes'][number], index: number): number {
    return folded.has(index) ? lane.foldedHeight : lane.height[density]
  }
  function placeOf(index: number): Place {
    const note = notes[index] as NoteData
    const lane = data.lanes[note.lane]
    if (lane === undefined) return note.places[density]
    if (folded.has(note.lane)) return { ...note.folded, y: laneTop(note.lane) + note.folded.y }
    const place = note.places[density]
    return { ...place, y: place.y - lane.top[density] + laneTop(note.lane) }
  }
  const placed = (index: number): Placed => ({ ...placeOf(index), seed: notes[index]?.seed ?? '' })

  function place(): void {
    notes.forEach((note, index) => {
      const sticky = stickies[index]
      if (sticky === null || sticky === undefined) return
      const at = placeOf(index)
      Object.assign(sticky.style, {
        left: `${at.x}px`,
        top: `${at.y}px`,
        width: `${at.w}px`,
        height: `${at.h}px`,
      })
      sticky.classList.toggle('mini', folded.has(note.lane))
      sticky.classList.toggle('dot', density === 'compact' && note.port && !folded.has(note.lane))
    })
    data.strips.forEach((strip, index) => {
      const element = strips[index]
      if (element === null || element === undefined) return
      const at = strip.places[density]
      const lane = data.lanes[strip.lane]
      element.hidden = folded.has(strip.lane)
      if (lane !== undefined) element.style.top = `${at.y - lane.top[density] + laneTop(strip.lane)}px`
    })
    lanes.forEach((lane, index) => {
      const top = laneTop(index)
      if (lane.tape !== null) lane.tape.style.top = `${top + 22}px`
      if (lane.fold !== null) {
        lane.fold.style.top = `${top + 74}px`
        lane.fold.setAttribute('aria-expanded', String(!folded.has(index)))
      }
      if (lane.count !== null) lane.count.style.top = `${top + 76}px`
    })
    const height = laneTop(data.lanes.length) + 30
    wall = { w: data.width[density], h: height }
    Object.assign(roll.style, { width: `${wall.w}px`, height: `${wall.h}px` })
    drawInk()
    drawMini()
  }

  /* ───── The marker: lanes' rules, time, reactions, views' lines, and the conditions of the dashed ones ───── */
  function drawInk(): void {
    const rules = data.lanes
      .slice(1)
      .map(
        (lane, index) =>
          `<path class="rule" d="${wobbly({ x: 26, y: laneTop(index + 1) }, { x: wall.w - 26, y: laneTop(index + 1) }, lane.name)}"/>`,
      )
    const time = `<path class="time" d="${wobbly({ x: 700, y: 62 }, { x: wall.w - 70, y: 62 }, 'time')} M${wall.w - 70},62 l-17,-10 M${wall.w - 70},62 l-17,10"/><text x="640" y="69">time</text>`
    const hits: string[] = []
    const arrows = data.edges.map((edge, index) => {
      const arrow = arrowOf(placed(edge.from), placed(edge.to), edge.certain)
      if (edge.tip !== null) hits.push(hitOf(arrow.curve, `[data-edge="${index}"] path`, edge.tip))
      const maybe =
        arrow.maybe === null
          ? ''
          : `<text class="maybe" x="${arrow.maybe.x}" y="${arrow.maybe.y}" text-anchor="middle">maybe</text>`
      return `<g data-edge="${index}"><path class="flow${edge.certain ? '' : ' uncertain'}" d="${arrow.curve}"/><path class="flow head" d="${arrow.head}"/>${maybe}</g>`
    })
    const braces = data.braces.map((brace, index) => {
      if (folded.has(brace.lane)) return ''
      const drawn = braceOf(
        placed(brace.from),
        brace.ends.map(placed),
        brace.spine,
        brace.over === null ? null : placed(brace.over),
      )
      const branches = drawn.branches.map((branch, at) => {
        const certain = brace.certain[at] ?? true
        const condition = brace.tips[at]
        if (condition !== null && condition !== undefined)
          hits.push(hitOf(branch.target, `[data-brace="${index}"] [data-item="${at}"]`, condition))
        return `<path class="flow${certain ? '' : ' uncertain'}" data-item="${at}" d="${branch.line}"/><path class="flow head" data-item="${at}" d="${branch.head}"/>`
      })
      return `<g data-brace="${index}"><path class="flow" d="${drawn.lead}"/>${branches.join('')}</g>`
    })
    ink.innerHTML = `<svg class="ink" width="${wall.w}" height="${wall.h}">${rules.join('')}${time}${arrows.join('')}${braces.join('')}</svg>`
    element('hits').innerHTML =
      `<svg class="ink hits" width="${wall.w}" height="${wall.h}">${hits.join('')}</svg>`
    element('labels').innerHTML = data.lanes
      .flatMap((lane, index) =>
        folded.has(index)
          ? lane.labels.map(
              (label) =>
                `<span class="fold-label" style="left:${label.x}px;top:${laneTop(index) + label.y}px">${escapedText(label.text)}</span>`,
            )
          : [],
      )
      .join('')
    light(pinned)
  }
  function hitOf(path: string, lines: string, condition: string): string {
    return `<path class="hit" d="${path}" data-line="${escapedText(lines)}" tabindex="0" role="img" aria-label="${escapedText(condition)}" data-tip="${escapedText(condition)}"/>`
  }
  function escapedText(text: string): string {
    return text
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
  }

  /* ───── The camera: walking along the wall ───── */
  const camera = { x: 0, y: 0, k: 1 }
  let flight = 0
  const viewport = (): { w: number; h: number; top: number } => ({
    w: stage.clientWidth,
    h: stage.clientHeight,
    top: element('bar').getBoundingClientRect().bottom + 10,
  })
  function apply(): void {
    world.style.transform = `translate(${camera.x.toFixed(1)}px, ${camera.y.toFixed(1)}px) scale(${camera.k.toFixed(4)})`
    world.style.setProperty('--inv', (1 / camera.k).toFixed(3))
    world.dataset.zoom = camera.k < 0.46 ? 'far' : 'near'
    element('zoomPct').textContent = `${Math.round(camera.k * 100)}%`
    drawView()
  }
  function fly(target: typeof camera, instant = false): void {
    cancelAnimationFrame(flight)
    const from = { ...camera }
    const started = performance.now()
    const still = instant || matchMedia('(prefers-reduced-motion: reduce)').matches
    const span = still ? 0 : 420
    const step = (now: number): void => {
      const share = span === 0 ? 1 : Math.min(1, (now - started) / span)
      const eased = 1 - (1 - share) ** 3
      camera.k = from.k + (target.k - from.k) * eased
      camera.x = from.x + (target.x - from.x) * eased
      camera.y = from.y + (target.y - from.y) * eased
      apply()
      if (share < 1) flight = requestAnimationFrame(step)
    }
    step(started)
  }
  function frame(x: number, y: number, w: number, h: number, pad = 40, most = 1.15): typeof camera {
    const view = viewport()
    const k = Math.max(0.05, Math.min(most, (view.w - 2 * pad) / w, (view.h - view.top - 2 * pad) / h))
    return { k, x: (view.w - w * k) / 2 - x * k, y: view.top + (view.h - view.top - h * k) / 2 - y * k }
  }
  const wholeWall = (): typeof camera => frame(0, 0, wall.w, wall.h, 30, 1)
  function firstLook(): typeof camera {
    const view = viewport()
    const k = Math.max(0.55, Math.min(1, (view.w - 48) / wall.w))
    return { k, x: Math.max(24, (view.w - wall.w * k) / 2), y: view.top + 12 }
  }
  function laneFrame(lane: number): typeof camera {
    const view = viewport()
    const k = Math.max(0.55, Math.min(1.05, (view.w - 68) / wall.w))
    const height = heightOf(data.lanes[lane] ?? (data.lanes[0] as WallData['lanes'][number]), lane)
    return {
      k,
      x: Math.max(24, (view.w - wall.w * k) / 2),
      y: view.top + Math.max(14, (view.h - view.top - height * k) / 2) - laneTop(lane) * k,
    }
  }
  function centreOn(index: number, k = Math.max(camera.k, 0.95)): typeof camera {
    const view = viewport()
    const side = inspector.hidden ? 0 : 340
    const at = placeOf(index)
    return {
      k,
      x: (view.w - side) / 2 - (at.x + at.w / 2) * k,
      y: view.top + (view.h - view.top) / 2 - (at.y + at.h / 2) * k,
    }
  }
  function seen(index: number): boolean {
    const view = viewport()
    const at = placeOf(index)
    const x = at.x * camera.k + camera.x
    const y = at.y * camera.k + camera.y
    return x > 20 && y > view.top && x + at.w * camera.k < view.w - 20 && y + at.h * camera.k < view.h - 20
  }
  function zoomAt(factor: number, px: number, py: number): void {
    const k = Math.max(0.12, Math.min(2.5, camera.k * factor))
    fly({ k, x: px - ((px - camera.x) / camera.k) * k, y: py - ((py - camera.y) / camera.k) * k }, true)
  }
  stage.addEventListener(
    'wheel',
    (event) => {
      event.preventDefault()
      if (event.ctrlKey || event.metaKey)
        return zoomAt(Math.exp(-event.deltaY * 0.01), event.clientX, event.clientY)
      const unit = event.deltaMode === 1 ? 32 : 1
      fly(
        {
          k: camera.k,
          x: camera.x - (event.shiftKey ? event.deltaY : event.deltaX) * unit,
          y: camera.y - (event.shiftKey ? 0 : event.deltaY) * unit,
        },
        true,
      )
    },
    { passive: false },
  )
  let drag: { x: number; y: number; cx: number; cy: number } | null = null
  let moved = false
  stage.addEventListener('pointerdown', (event) => {
    if (event.button !== 0 || (event.target as Element).closest('.card')) return
    moved = false
    drag = { x: event.clientX, y: event.clientY, cx: camera.x, cy: camera.y }
  })
  stage.addEventListener('pointermove', (event) => {
    if (drag === null) return
    const dx = event.clientX - drag.x
    const dy = event.clientY - drag.y
    if (!moved && Math.hypot(dx, dy) < 5) return
    if (!moved) stage.classList.add('panning')
    moved = true
    cancelAnimationFrame(flight)
    camera.x = drag.cx + dx
    camera.y = drag.cy + dy
    apply()
  })
  for (const end of ['pointerup', 'pointercancel']) {
    stage.addEventListener(end, () => {
      drag = null
      stage.classList.remove('panning')
    })
  }

  /* ───── The minimap: the wall seen from the door ───── */
  const mini = element('mini')
  let scale = 1
  function drawMini(): void {
    scale = Math.min(236 / wall.w, 190 / wall.h)
    Object.assign(mini.style, { width: `${wall.w * scale}px`, height: `${wall.h * scale}px` })
    mini.innerHTML = `${notes
      .map((note, index) => {
        const at = placeOf(index)
        return `<i style="left:${at.x * scale}px;top:${at.y * scale}px;width:${Math.max(2.5, at.w * scale)}px;height:${Math.max(2.5, at.h * scale)}px;--c:var(--${note.kind})"></i>`
      })
      .join('')}<div class="view"></div>`
    drawView()
  }
  function drawView(): void {
    const rectangle = mini.querySelector<HTMLElement>('.view')
    if (rectangle === null) return
    const view = viewport()
    Object.assign(rectangle.style, {
      left: `${(-camera.x / camera.k) * scale}px`,
      top: `${(-camera.y / camera.k) * scale}px`,
      width: `${(view.w / camera.k) * scale}px`,
      height: `${(view.h / camera.k) * scale}px`,
    })
  }
  function miniTo(event: PointerEvent): void {
    const box = mini.getBoundingClientRect()
    const view = viewport()
    fly(
      {
        k: camera.k,
        x: view.w / 2 - ((event.clientX - box.left) / scale) * camera.k,
        y: view.h / 2 - ((event.clientY - box.top) / scale) * camera.k,
      },
      true,
    )
  }
  mini.addEventListener('pointerdown', (event) => miniTo(event))
  mini.addEventListener('pointermove', (event) => {
    if (event.buttons === 1) miniTo(event)
  })

  /* ───── Following a story ───── */
  function light(index: number | null): void {
    const lit = new Set(index === null ? [] : (notes[index]?.story ?? []))
    world.classList.toggle('following', index !== null)
    stickies.forEach((sticky, at) => {
      sticky?.classList.toggle('lit', lit.has(at))
    })
    data.strips.forEach((strip, at) => {
      strips[at]?.classList.toggle('lit', lit.has(strip.note))
    })
    data.edges.forEach((edge, at) => {
      for (const path of ink.querySelectorAll(`[data-edge="${at}"] path`))
        path.classList.toggle('lit', lit.has(edge.from) && lit.has(edge.to))
    })
    data.braces.forEach((brace, at) => {
      const on = (item: number): boolean => lit.has(brace.from) && lit.has(brace.commands[item] ?? -1)
      for (const path of ink.querySelectorAll<SVGPathElement>(`[data-brace="${at}"] path`)) {
        const item = path.dataset.item
        path.classList.toggle(
          'lit',
          item === undefined ? brace.commands.some((_, every) => on(every)) : on(Number(item)),
        )
      }
    })
  }
  function pin(index: number | null): void {
    if (pinned !== null) stickies[pinned]?.classList.remove('pinned')
    pinned = index
    if (index !== null) stickies[index]?.classList.add('pinned')
    light(index)
    inspector.hidden = index === null
    inspector.innerHTML = index === null ? '' : (notes[index]?.card ?? '')
  }
  const nodeOf = (target: EventTarget | null): number | null => {
    const sticky = (target as Element | null)?.closest?.('.sticky') as HTMLElement | null | undefined
    return sticky?.dataset.node === undefined ? null : Number(sticky.dataset.node)
  }
  roll.addEventListener('pointerover', (event) => {
    const index = nodeOf(event.target)
    if (index !== null && drag === null) light(index)
  })
  roll.addEventListener('pointerout', (event) => {
    if (nodeOf(event.target) !== null && nodeOf(event.relatedTarget) === null) light(pinned)
  })
  roll.addEventListener('focusin', (event) => {
    const index = nodeOf(event.target)
    if (index === null) return
    light(index)
    if (!seen(index)) fly(centreOn(index))
  })
  roll.addEventListener('focusout', () => light(pinned))
  stage.addEventListener('click', (event) => {
    if (moved) return
    const target = event.target as Element
    if (target.closest('.jump.more') !== null) return
    const jump = target.closest<HTMLElement>('[data-jump]')
    const index = nodeOf(target)
    if (jump !== null && index !== null)
      return follow(notes[index]?.jumps[Number(jump.dataset.jump)]?.to ?? null)
    const tape = target.closest<HTMLElement>('.lane-tape')
    if (tape !== null) return go({ lane: Number(tape.dataset.lane) })
    const fold = target.closest<HTMLElement>('.lane-fold')
    if (fold !== null) return foldLane(Number(fold.dataset.fold))
    if (index !== null) return index === pinned ? go({}, { keep: true }) : go({ at: index })
    if (pinned !== null) go({}, { keep: true })
  })
  function foldLane(lane: number): void {
    if (folded.has(lane)) folded.delete(lane)
    else folded.add(lane)
    redraw()
  }
  function redraw(): void {
    place()
    apply()
    element('dense').setAttribute('aria-pressed', String(density === 'compact'))
    history.replaceState(history.state, '', hashOf(placeOfHash()))
  }
  function densify(): void {
    density = density === 'compact' ? 'roomy' : 'compact'
    redraw()
  }

  /* ───── Going somewhere, and coming back ───── */
  interface Spot {
    at?: number
    lane?: number
  }
  /** A wall with more use cases than a screen holds opens folded, and its link then always says which lanes are folded. */
  const USE_CASES_OF_AN_UNFOLDED_WALL = 40
  const large = data.lanes.reduce((sum, lane) => sum + lane.useCases, 0) > USE_CASES_OF_AN_UNFOLDED_WALL
  const foldPart = (): string =>
    (folded.size > 0 || large
      ? `&fold=${[...folded].map((lane) => encodeURIComponent(data.lanes[lane]?.name ?? '')).join(',')}`
      : '') + (density === 'compact' ? '&rows=compact' : '')
  function hashOf(spot: Spot): string {
    if (spot.at !== undefined) return `#at=${encodeURIComponent(notes[spot.at]?.slug ?? '')}${foldPart()}`
    if (spot.lane !== undefined)
      return `#lane=${encodeURIComponent(data.lanes[spot.lane]?.name ?? '')}${foldPart()}`
    return `#${foldPart().slice(1)}`
  }
  function placeOfHash(): Spot {
    const asked = hash()
    const at = notes.findIndex((note) => !note.badge && note.slug === asked.get('at'))
    if (at >= 0) return { at }
    const lane = data.lanes.findIndex((candidate) => candidate.name === asked.get('lane'))
    return lane >= 0 ? { lane } : {}
  }
  function show(spot: Spot, options: { keep?: boolean; instant?: boolean; first?: boolean } = {}): void {
    for (const button of element('lanes').querySelectorAll<HTMLElement>('button')) {
      const lane = button.dataset.lane === '' ? undefined : Number(button.dataset.lane)
      button.setAttribute('aria-pressed', String(spot.at === undefined && lane === spot.lane))
    }
    unfoldTheLaneOf(spot)
    pin(spot.at ?? null)
    if (options.keep === true) return
    const camera =
      spot.at !== undefined
        ? notes[spot.at]?.kind === 'actor'
          ? firstLook()
          : centreOn(spot.at)
        : spot.lane !== undefined
          ? laneFrame(spot.lane)
          : options.first
            ? firstLook()
            : wholeWall()
    fly(camera, options.instant)
  }
  /** A post-it shown in a folded lane would be a small square: its lane unfolds. */
  function unfoldTheLaneOf(spot: Spot): void {
    const lane = spot.at === undefined ? undefined : notes[spot.at]?.lane
    if (lane === undefined || !folded.has(lane)) return
    folded.delete(lane)
    redraw()
  }
  function go(spot: Spot, options: { keep?: boolean } = {}): void {
    depth += 1
    history.pushState({ depth }, '', hashOf(spot))
    back.disabled = false
    show(spot, options)
  }
  function follow(to: number | null): void {
    if (to !== null) go({ at: to })
  }
  /** A wall opened from a pictogram of another wall goes back to it. */
  const cameFromAWall = /\/docs\/event-storming\//.test(document.referrer)
  back.disabled = !cameFromAWall
  addEventListener('popstate', (event) => {
    depth = (event.state as { depth?: number } | null)?.depth ?? 0
    back.disabled = depth === 0 && !cameFromAWall
    show(placeOfHash())
  })
  back.addEventListener('click', () => history.back())
  document.addEventListener('click', (event) => {
    const target = event.target as Element
    const goTo = target.closest<HTMLElement>('[data-go]')
    const close = target.closest<HTMLElement>('[data-close]')
    if (goTo !== null) {
      story.hidden = true
      go({ at: Number(goTo.dataset.go) })
    }
    if (close !== null) {
      if (close.dataset.close === 'inspector') go({}, { keep: true })
      else element(close.dataset.close ?? '').hidden = true
    }
    if (target.closest('[data-copy]') !== null) void navigator.clipboard?.writeText(location.href)
  })

  /* ───── Search, legend, text, lights ───── */
  let found: number[] = []
  let current = 0
  function find(): void {
    const asked = search.value.trim().toLowerCase()
    const slugs = new Set<string>()
    found =
      asked === ''
        ? []
        : notes.flatMap((note, index) => {
            if (!asked.split(/\s+/).every((word) => note.search.includes(word)) || slugs.has(note.slug))
              return []
            slugs.add(note.slug)
            return [index]
          })
    current = 0
    world.classList.toggle('searching', asked !== '')
    const hit = new Set(found.map((index) => notes[index]?.slug))
    stickies.forEach((sticky, index) => {
      sticky?.classList.toggle('hit', asked !== '' && hit.has(notes[index]?.slug))
    })
    results.innerHTML =
      found
        .slice(0, 9)
        .map((index, rank) => {
          const note = notes[index] as NoteData
          return `<button role="option" class="${rank === 0 ? 'cur' : ''}" data-hit="${rank}"><span class="dot" style="--c:var(--${note.kind})"></span><span>${escapedText(note.label)}<small>${escapedText(data.lanes[note.lane]?.label ?? '')}</small></span></button>`
        })
        .join('') +
      (asked !== '' && found.length === 0
        ? '<button disabled>Nothing on this wall. <small>The search reads labels, class names and feature titles.</small></button>'
        : '')
  }
  function pick(rank: number): void {
    const index = found[rank]
    if (index === undefined) return
    search.value = ''
    find()
    search.blur()
    go({ at: index })
  }
  search.addEventListener('input', find)
  search.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') pick(current)
    if (event.key === 'Escape') {
      search.value = ''
      find()
      search.blur()
    }
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault()
      const shown = Math.max(1, Math.min(9, found.length))
      current = (current + (event.key === 'ArrowDown' ? 1 : -1) + shown) % shown
      for (const [rank, button] of [...results.children].entries())
        button.classList.toggle('cur', rank === current)
    }
  })
  results.addEventListener('click', (event) => {
    const hit = (event.target as Element).closest<HTMLElement>('[data-hit]')
    if (hit !== null) pick(Number(hit.dataset.hit))
  })
  element('kinds').addEventListener('click', (event) => {
    const button = (event.target as Element).closest<HTMLElement>('[data-kind]')
    if (button === null) return
    const on = button.getAttribute('aria-pressed') !== 'true'
    button.setAttribute('aria-pressed', String(on))
    world.classList.toggle(`off-${button.dataset.kind}`, !on)
  })
  element('who').addEventListener('click', (event) => {
    const button = (event.target as Element).closest<HTMLElement>('[data-who]')
    if (button === null) return
    const on = button.getAttribute('aria-pressed') !== 'true'
    for (const other of element('who').querySelectorAll('[data-who]'))
      other.setAttribute('aria-pressed', 'false')
    button.setAttribute('aria-pressed', String(on))
    go(on ? { at: Number(button.dataset.who) } : {}, { keep: true })
  })
  element('legendFold').addEventListener('click', () => element('legend').classList.toggle('folded'))
  element('lanes').addEventListener('click', (event) => {
    const button = (event.target as Element).closest<HTMLElement>('button')
    if (button !== null) go(button.dataset.lane === '' ? {} : { lane: Number(button.dataset.lane) })
  })
  element('storyBtn').addEventListener('click', () => {
    story.hidden = !story.hidden
  })
  function lights(): void {
    const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark'
    document.documentElement.dataset.theme = theme
    try {
      localStorage.setItem('es-theme', theme)
    } catch {
      /* A private window keeps no choice: the page still switches. */
    }
  }
  element('themeBtn').addEventListener('click', lights)
  element('helpBtn').addEventListener('click', () => {
    element('help').hidden = !element('help').hidden
  })
  element('zoomIn').addEventListener('click', () =>
    zoomAt(1.25, stage.clientWidth / 2, stage.clientHeight / 2),
  )
  element('zoomOut').addEventListener('click', () =>
    zoomAt(0.8, stage.clientWidth / 2, stage.clientHeight / 2),
  )
  element('fit').addEventListener('click', () => go({}))
  element('dense').addEventListener('click', densify)

  /* A dashed line says when it happens, under the mouse and under the keyboard's focus. */
  for (const [type, on] of [
    ['pointerover', true],
    ['pointerout', false],
    ['focusin', true],
    ['focusout', false],
  ] as const) {
    element('hits').addEventListener(type, (event) => {
      const hit = (event.target as Element).closest<SVGElement>('.hit')
      if (hit === null) return
      for (const path of ink.querySelectorAll(hit.dataset.line ?? '')) path.classList.toggle('on', on)
    })
  }

  /* Tooltips, for the mouse and for the keyboard. */
  function tipOn(target: HTMLElement | SVGElement): void {
    tip.textContent = target.dataset.tip ?? ''
    const box = target.getBoundingClientRect()
    tip.classList.add('on')
    const width = tip.offsetWidth
    tip.style.left = `${Math.max(8, Math.min(innerWidth - width - 8, box.left + box.width / 2 - width / 2))}px`
    const gap = target.classList.contains('hit') ? 18 : 8
    tip.style.top = `${box.top < 90 ? box.bottom + gap : box.top - tip.offsetHeight - gap}px`
  }
  for (const [on, off] of [
    ['pointerover', 'pointerout'],
    ['focusin', 'focusout'],
  ] as const) {
    document.addEventListener(on, (event) => {
      const target = (event.target as Element).closest?.<HTMLElement>('[data-tip]')
      if (target !== null && target !== undefined) tipOn(target)
    })
    document.addEventListener(off, () => tip.classList.remove('on'))
  }

  /* ───── The keyboard ───── */
  function neighbour(index: number, dx: number, dy: number): number | null {
    const from = placeOf(index)
    let best: number | null = null
    let least = Number.POSITIVE_INFINITY
    notes.forEach((_, other) => {
      if (other === index) return
      const to = placeOf(other)
      const across = { x: to.x + to.w / 2 - from.x - from.w / 2, y: to.y + to.h / 2 - from.y - from.h / 2 }
      const along = across.x * dx + across.y * dy
      if (along <= 4) return
      const cost = along + (Math.abs(across.x * dy) + Math.abs(across.y * dx)) * 2.6
      if (cost < least) {
        least = cost
        best = other
      }
    })
    return best
  }
  function focusOn(index: number): void {
    stickies.forEach((sticky, other) => {
      if (sticky !== null && sticky !== undefined) sticky.tabIndex = other === index ? 0 : -1
    })
    stickies[index]?.focus({ preventScroll: true })
  }
  const ARROWS: Record<string, [number, number]> = {
    ArrowRight: [1, 0],
    ArrowLeft: [-1, 0],
    ArrowDown: [0, 1],
    ArrowUp: [0, -1],
  }
  document.addEventListener('keydown', (event) => {
    if (event.target === search || event.altKey || event.ctrlKey || event.metaKey) return
    const index = nodeOf(document.activeElement)
    const arrow = ARROWS[event.key]
    if (arrow !== undefined) {
      event.preventDefault()
      const next = index === null ? null : neighbour(index, arrow[0], arrow[1])
      if (next !== null) focusOn(next)
      else if (index === null)
        fly({ k: camera.k, x: camera.x - arrow[0] * 140, y: camera.y - arrow[1] * 140 }, true)
      return
    }
    const key = event.key.toLowerCase()
    if ((key === 'enter' || key === ' ') && index !== null && event.target === stickies[index]) {
      event.preventDefault()
      return index === pinned ? go({}, { keep: true }) : go({ at: index }, { keep: seen(index) })
    }
    if (event.target instanceof Element && event.target.closest('input, textarea, select') !== null) return
    if (key === '/') {
      event.preventDefault()
      search.focus()
    } else if (key === 'f' || key === '0') go({})
    else if (key === '1') zoomAt(1 / camera.k, stage.clientWidth / 2, stage.clientHeight / 2)
    else if (key === '+' || key === '=') zoomAt(1.25, stage.clientWidth / 2, stage.clientHeight / 2)
    else if (key === '-') zoomAt(0.8, stage.clientWidth / 2, stage.clientHeight / 2)
    else if (key === 'j' && index !== null) follow(notes[index]?.jumps[0]?.to ?? null)
    else if (key === 't') element('storyBtn').click()
    else if (key === 'l') element('legendFold').click()
    else if (key === 'd') lights()
    else if (key === 'c') densify()
    else if (key === '?') element('helpBtn').click()
    else if (key === 'escape') {
      element('help').hidden = true
      story.hidden = true
      if (pinned !== null) go({}, { keep: true })
    }
  })

  /* ───── The first look ───── */
  if (hash().has('fold')) {
    for (const name of (hash().get('fold') ?? '').split(',')) {
      const lane = data.lanes.findIndex((candidate) => candidate.name === name)
      if (lane >= 0) folded.add(lane)
    }
  } else if (large) {
    data.lanes.forEach((_, lane) => {
      folded.add(lane)
    })
  }
  place()
  element('dense').setAttribute('aria-pressed', String(density === 'compact'))
  history.replaceState({ depth: 0 }, '', large ? hashOf(placeOfHash()) : location.hash || '#')
  show(placeOfHash(), { instant: true, first: true })
  const asked = new URLSearchParams(location.search)
  const question = asked.get('q')
  if (question !== null) {
    search.value = question
    find()
  }
  if (asked.get('view') === 'text') story.hidden = false
  addEventListener('resize', drawView)
}
