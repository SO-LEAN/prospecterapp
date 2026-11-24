// Drives the mockup in headless Chrome over the DevTools protocol: real clicks and key presses, then screenshots.
import { spawn } from 'node:child_process'
import { writeFileSync, rmSync } from 'node:fs'
const [, , here] = process.argv
const page = `file://${here}/mockup.html`
const chrome = spawn('google-chrome', ['--headless=new', '--no-sandbox', '--disable-gpu', '--hide-scrollbars', '--remote-debugging-port=9377', `--user-data-dir=${here}/.chrome`, '--window-size=1700,1000', 'about:blank'], { stdio: 'ignore' })
const sleep = (ms) => new Promise((done) => setTimeout(done, ms))
let target
for (let i = 0; i < 50 && !target; i++) { await sleep(200); try { target = (await (await fetch('http://127.0.0.1:9377/json')).json()).find((t) => t.type === 'page') } catch {} }
const ws = new WebSocket(target.webSocketDebuggerUrl)
await new Promise((open) => ws.addEventListener('open', open))
let id = 0
const waiting = new Map(), errors = []
ws.addEventListener('message', (message) => {
  const data = JSON.parse(message.data)
  if (data.id) waiting.get(data.id)?.(data)
  if (data.method === 'Runtime.exceptionThrown') errors.push(data.params.exceptionDetails.exception?.description ?? data.params.exceptionDetails.text)
  if (data.method === 'Runtime.consoleAPICalled' && data.params.type === 'error') errors.push(data.params.args.map((a) => a.value).join(' '))
})
const send = (method, params = {}) => new Promise((done) => { waiting.set(++id, done); ws.send(JSON.stringify({ id, method, params })) })
const js = async (expression) => { const r = await send('Runtime.evaluate', { expression, returnByValue: true }); if (r.result.exceptionDetails) throw new Error(expression + ' → ' + r.result.exceptionDetails.exception?.description); return r.result.result.value }
await send('Runtime.enable'); await send('Page.enable')
await send('Emulation.setDeviceMetricsOverride', { width: 1700, height: 1000, deviceScaleFactor: 1, mobile: false })
const open = async (suffix, height = 1000) => { await send('Emulation.setDeviceMetricsOverride', { width: 1700, height, deviceScaleFactor: 1, mobile: false }); await send('Page.navigate', { url: 'about:blank' }); await sleep(100); await send('Page.navigate', { url: page + suffix }); await sleep(900); await send('Page.bringToFront'); await send('Emulation.setFocusEmulationEnabled', { enabled: true }) }
const shot = async (name) => { await sleep(600); const r = await send('Page.captureScreenshot', { format: 'png' }); writeFileSync(`${here}/shots/${name}.png`, Buffer.from(r.result.data, 'base64')) }
const centre = (selector) => js(`(() => { const b = document.querySelector(${JSON.stringify(selector)}).getBoundingClientRect(); return [b.left + b.width / 2, b.top + b.height / 2] })()`)
const click = async (selector) => { const [x, y] = await centre(selector); for (const type of ['mouseMoved', 'mousePressed', 'mouseReleased']) await send('Input.dispatchMouseEvent', { type, x, y, button: type === 'mouseMoved' ? 'none' : 'left', buttons: type === 'mousePressed' ? 1 : 0, clickCount: 1 }); await sleep(600) }
const KEYS = { Tab: 9, Enter: 13, Escape: 27, ArrowRight: 39, ArrowLeft: 37, ArrowDown: 40, ArrowUp: 38 }
const press = async (key, modifiers = 0) => {
  const base = { key, code: KEYS[key] ? key : 'Key' + key.toUpperCase(), windowsVirtualKeyCode: KEYS[key] ?? key.toUpperCase().charCodeAt(0), modifiers }
  const r = await send('Input.dispatchKeyEvent', { type: KEYS[key] && key !== 'Enter' ? 'rawKeyDown' : 'keyDown', ...base, ...(KEYS[key] && key !== 'Enter' ? {} : { text: key === 'Enter' ? '\r' : key }) })
  if (r.error) errors.push(JSON.stringify(r.error))
  await send('Input.dispatchKeyEvent', { type: 'keyUp', ...base }); await sleep(450)
}
let failed = 0
/* Every label of every post-it: inside its own paper, and under no other post-it (bounding boxes, so a tilt counts). */
const OVERLAPS = `(() => {
  const bad = []
  const boxes = [...document.querySelectorAll('.sticky:not(.mini):not(.dot)')].map((el, i) => ({ el, i, z: Number(getComputedStyle(el).zIndex) || 0, r: el.getBoundingClientRect(), name: (el.querySelector('.txt')?.textContent ?? 'badge') }))
  const cross = (a, b) => a.left < b.right - 0.5 && a.right > b.left + 0.5 && a.top < b.bottom - 0.5 && a.bottom > b.top + 0.5
  for (const a of boxes) {
    const paper = a.el.querySelector('.paper')
    if (paper.scrollHeight > paper.clientHeight + 1 || paper.scrollWidth > paper.clientWidth + 1) bad.push(a.name + ' overflows its post-it')
    for (const label of a.el.querySelectorAll('.txt, .via, .when, .from')) {
      const r = label.getBoundingClientRect()
      for (const b of boxes) if (b !== a && (b.z > a.z || (b.z === a.z && b.i > a.i)) && cross(r, b.r)) bad.push('“' + label.textContent + '” of ' + a.name + ' is under ' + b.name)
    }
    if (a.el.classList.contains('k-hotspot')) for (const b of boxes) if (b !== a && cross(a.r, b.r)) bad.push('hotspot ' + a.name + ' touches ' + b.name)
  }
  return bad
})()`
const noOverlap = async (what) => { const bad = await js(OVERLAPS); check(`no label is covered or clipped (${what})`, bad.length === 0, bad) }
const hover = async (selector) => { const [x, y] = await centre(selector); await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: x + 1, y, button: 'none' }); await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x, y, button: 'none' }); await sleep(350) }
const tip = () => js(`document.getElementById('tip').classList.contains('on') ? document.getElementById('tip').textContent : null`)
const check = (what, ok, got) => { if (!ok) failed++; console.log(`${ok ? 'ok  ' : 'FAIL'} ${what}${ok ? '' : ' — got ' + JSON.stringify(got)}`) }
const nodeOf = (label, kind) => `[...document.querySelectorAll('.sticky.k-${kind}')].find((el) => el.querySelector('.txt')?.textContent === ${JSON.stringify(label)})`
const mark = async (label, kind, name) => js(`${nodeOf(label, kind)}.setAttribute('data-test', '${name}')`)

// 1. Booking: the fold button, by a real click
await open('?theme=light')
check('first look shows the first lane', await js(`document.querySelector('.lane-tape').getBoundingClientRect().top < 400`))
check('actor stands left of its read model', await js(`${nodeOf('Member', 'actor')}.getBoundingClientRect().left < ${nodeOf('My booking page', 'readModel')}.getBoundingClientRect().left`))
check('one Member, one Anyone on the wall', await js(`[...document.querySelectorAll('.k-actor:not(.badge) .txt')].map((e) => e.textContent).sort().join()`) === 'Anyone with the link,Member')
check('the method name is whole and inside its post-it', await js(`[...document.querySelectorAll('.via')].every((via) => { const a = via.getBoundingClientRect(), p = via.closest('.paper').getBoundingClientRect(); return via.textContent.endsWith('()') && a.left >= p.left && a.right <= p.right + 0.5 && via.scrollWidth <= via.clientWidth + 1 })`))
check('no "?" is drawn on the wall', await js(`document.querySelectorAll('.guess, .guessed').length`) === 0 && !(await js(`[...document.querySelectorAll('svg.ink text')].map((t) => t.textContent).join()`)).includes('?'))
check('My booking page offers seven commands, three only sometimes', await js(`(() => { const g = [...document.querySelectorAll('[data-bracket]')].map((g) => [g.querySelectorAll('.flow[data-item]:not(.head)').length, g.querySelectorAll('.flow.uncertain').length]); return JSON.stringify(g) })()`) === '[[7,3],[1,0]]', await js(`JSON.stringify([...document.querySelectorAll('[data-bracket]')].map((g) => [g.querySelectorAll('.flow[data-item]:not(.head)').length, g.querySelectorAll('.flow.uncertain').length]))`))
check('the three conditional offers are open, close and change link', await js(`(() => { const mid = (label) => { const b = ${nodeOf('X', 'command').replace('"X"', 'label')}.getBoundingClientRect(); return b.top + b.height / 2 }; const dashed = [...document.querySelectorAll('[data-bracket="0"] .flow.uncertain')].map((p) => p.getBoundingClientRect().top); return ['Open booking', 'Close booking', 'Change booking link'].every((label) => dashed.some((y) => Math.abs(y - mid(label)) < 24)) })()`))
check('one gap on Booking: Book slot is offered nowhere', await js(`[...document.querySelectorAll('.k-hotspot .txt')].map((e) => e.textContent).sort().join()`) === 'No event recorded,Offered where?,Who reads this?', await js(`[...document.querySelectorAll('.k-hotspot .txt')].map((e) => e.textContent).sort().join()`))
await noOverlap('first look')
await shot('light')
check('a dashed line has a wide target', await js(`Math.min(...[...document.querySelectorAll('.hit')].map((hit) => parseFloat(getComputedStyle(hit).strokeWidth)))`) >= 24 && await js(`document.querySelectorAll('.hit').length`) === 4, await js(`document.querySelectorAll('.hit').length`))
await click('.lane-fold[data-fold="BookingPage"]')
check('click folds the lane', await js(`document.querySelectorAll('.sticky.mini').length`) > 20, await js(`document.querySelectorAll('.sticky.mini').length`))
check('the link holds the fold', (await js('location.hash')).includes('fold=BookingPage'), await js('location.hash'))
check('a folded lane names its steps', (await js(`[...document.querySelectorAll('.fold-label')].map((e) => e.textContent).join()`)).includes('Open booking'))
check('folded names do not run into each other', await js(`(() => { const r = [...document.querySelectorAll('.fold-label')].map((e) => e.getBoundingClientRect()); return r.every((a, i) => r.every((b, j) => i >= j || a.right <= b.left || b.right <= a.left || a.bottom <= b.top || b.bottom <= a.top)) })()`))
await shot('folded')
await click('.lane-fold[data-fold="BookingPage"]')
check('click unfolds it', await js(`document.querySelectorAll('.sticky.mini').length`) === 0)

// 2. Pinning a story by a real click, and letting go
await mark('Book slot', 'command', 'book')
await js(`document.querySelector('[data-test=book]').scrollIntoView`) // no-op: the camera, not the page, scrolls
await click('#lanes button[data-lane="PublicLink"]')
await mark('Book slot', 'command', 'book')
await click('[data-test=book] .paper')
check('click pins the post-it', await js(`${nodeOf('Book slot', 'command')}.classList.contains('pinned')`))
check('its card opens', await js(`!document.getElementById('inspector').hidden && document.querySelector('#inspector .name').textContent`) === 'Book slot')
const lit = await js(`[...document.querySelectorAll('.sticky.lit .txt')].map((e) => e.textContent)`)
check('the story lights end to end', ['Anyone with the link', 'Book slot', 'Slot booked', 'Publish slot booked', 'Identify booker'].every((label) => lit.includes(label)), lit)
check('a view that does not offer the command stays out of its story', !lit.includes('Public booking page'), lit)
check('the card says no view offers it', (await js(`document.getElementById('inspector').textContent`)).includes('no view the sender reads here'))
check('the link holds the post-it', (await js('location.hash')).includes('at=command%3ABookSlot') || (await js('location.hash')).includes('at=command:BookSlot'), await js('location.hash'))
await noOverlap('Book slot pinned, under the mouse')
await shot('pin')
await press('Escape')
check('Escape lets go', await js(`document.getElementById('inspector').hidden && !document.querySelector('.sticky.pinned')`))
await open('?theme=light#at=command:OpenBooking')
await press('Escape')
await mark('Open booking', 'command', 'open')
await click('[data-test=open] .paper')
const offered = await js(`[...document.querySelectorAll('.sticky.lit .txt')].map((e) => e.textContent)`)
check('an offered command lights its view and its reader', ['Member', 'My booking page', 'Open booking', 'Booking opened'].every((label) => offered.includes(label)) && !offered.includes('Close booking'), offered)
check('its card names the link and its condition', /Offered by.*My booking page.*open.*only while the page is closed/s.test(await js(`document.getElementById('inspector').innerText`)), await js(`document.getElementById('inspector').innerText`))
check('only its own line is lit', await js(`document.querySelectorAll('[data-bracket="0"] .flow.lit[data-item]:not(.head)').length`) === 1)
await noOverlap('Open booking pinned, under the mouse')
await shot('pin-offered')
await press('Escape')
await hover('.hit[aria-label*="closed"]')
check('hovering the dashed offer says its real condition', await tip() === 'Offered only while the page is closed', await tip())
check('the hovered line stands out', await js(`document.querySelectorAll('.flow.on').length`) === 2)
await shot('condition')
await click('#lanes button[data-lane="Appointment"]')
await hover('.hit[aria-label^="Maybe"]')
check('hovering the dashed reaction says maybe and its condition', await tip() === 'Maybe: only when the exchange was erased', await tip())
await shot('condition-reaction')
await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: 5, y: 500, button: 'none' })
let stops = 0
await js(`document.querySelector('.sticky[tabindex="0"]').focus()`)
while (stops++ < 14 && !(await js(`document.activeElement.classList.contains('hit')`))) await press('Tab')
check('Tab reaches a dashed line, and its condition shows on focus', (await tip())?.startsWith('Offered only') || (await tip())?.startsWith('Maybe'), [stops, await tip()])
await press('Escape')
check('Escape lets go again', await js(`!document.querySelector('.sticky.pinned')`))

// 3. The keyboard
await open('?theme=light')
await press('Tab')
const first = await js(`document.activeElement.getAttribute('aria-label')`)
check('Tab enters the wall on a post-it', await js(`document.activeElement.classList.contains('sticky')`), first)
await press('ArrowRight')
const second = await js(`document.activeElement.getAttribute('aria-label')`)
check('→ moves to another post-it', second && second !== first, second)
await press('ArrowDown')
const third = await js(`document.activeElement.getAttribute('aria-label')`)
check('↓ moves again', third && third !== second, third)
await press('Enter')
check('Enter pins the focused post-it', await js(`document.activeElement.classList.contains('pinned') && !document.getElementById('inspector').hidden`))
await press('Escape')
await press('c')
check('C makes the rows compact', await js(`document.querySelectorAll('.sticky.dot').length`) > 5 && (await js('location.hash')).includes('rows=compact'), await js('location.hash'))
const tall = await js('WALL.h')
await js('document.activeElement.blur()')
await noOverlap('compact rows')
await shot('compact')
console.log('compact wall', await js('JSON.stringify(WALL)'))
await press('Tab')
await press('c')
check('C again gives the room back', await js('WALL.h') > tall, [tall, await js('WALL.h')])
console.log('comfortable wall', await js('JSON.stringify(WALL)'))
await press('/')
check('/ focuses the search', await js(`document.activeElement.id`) === 'q')
await send('Input.insertText', { text: 'slot' }); await sleep(300)
check('typing lists the matches', await js(`document.querySelectorAll('#results [data-hit]').length`) >= 3)
await press('Enter')
check('Enter flies to the first match and pins it', await js(`document.querySelector('.sticky.pinned .txt')?.textContent`) === 'Book slot', await js(`document.querySelector('.sticky.pinned .txt')?.textContent`))
await js('history.back()'); await sleep(600)
check('Back lets go and returns', await js(`!document.querySelector('.sticky.pinned')`))
await press('d')
check('D switches the lights', await js(`document.documentElement.dataset.theme`) === 'dark')
await press('f'); await sleep(300)
await shot('dark')

// 4. A short window opens the wall folded
await open('?theme=light', 430)
check('a wall far taller than the window opens folded', await js(`document.querySelectorAll('.lane-fold[aria-expanded="false"]').length`) === 3)
await shot('first-look-folded')

// 5. The fixture: an actor without a read model, a command two people send
await open('?theme=light&model=fixture')
const people = await js(`[...document.querySelectorAll('.k-actor:not(.badge) .txt')].map((e) => e.textContent)`)
check('each human actor stands once', people.slice().sort().join() === 'Admin,Auditor,Member', people)
check('an actor that reads nothing stands alone, without a hotspot', await js(`document.querySelectorAll('.k-hotspot').length`) === 0)
check('three marker lines: one from the view, two from the actors alone', await js(`JSON.stringify([...document.querySelectorAll('[data-bracket]')].map((g) => g.querySelectorAll('.flow[data-item]:not(.head)').length))`) === '[2,2,1]', await js(`JSON.stringify([...document.querySelectorAll('[data-bracket]')].map((g) => g.querySelectorAll('.flow[data-item]:not(.head)').length))`))
check('a long method name wraps inside its post-it', await js(`[...document.querySelectorAll('.via')].every((via) => { const a = via.getBoundingClientRect(), p = via.closest('.paper').getBoundingClientRect(); return a.left >= p.left && a.right <= p.right + 0.5 && a.bottom <= p.bottom })`))
await noOverlap('fixture')
await hover('.hit')
check('a condition the code does not state says so', await tip() === 'Offered only in some states', await tip())
await mark('Approve request', 'command', 'approve')
await click('[data-test=approve] .paper')
const senders = await js(`[...document.querySelectorAll('.sticky.lit.k-actor .txt')].map((e) => e.textContent).sort().join()`)
check('the shared command lights its three senders', senders === 'Admin,Auditor,Member', senders)
await shot('fixture')

await open('?theme=light', 2800); await shot('whole-wall')
await open('?theme=dark#at=actor:mcp'); await shot('pin-ai')
await open('?theme=light&view=text'); await shot('text')
check('no script error', errors.length === 0, errors)
ws.close(); chrome.kill()
await sleep(500)
rmSync(`${here}/.chrome`, { recursive: true, force: true }) // the profile this run created
process.exit(failed ? 1 : 0)
