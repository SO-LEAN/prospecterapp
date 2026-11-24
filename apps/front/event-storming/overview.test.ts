import { readdirSync, readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import type { Link, Model, Sticky } from './model.ts'
import { overviewOf } from './overview.ts'

/** Every context's model, as the generator last read them from the code. Vitest runs in the front's folder. */
const WALLS = 'public/docs/event-storming'
const MODELS = readdirSync(WALLS, { withFileTypes: true })
  .filter((entry) => entry.isDirectory())
  .map((entry) => JSON.parse(readFileSync(`${WALLS}/${entry.name}/model.json`, 'utf8')) as Model)
const ASSETS = { style: '', font: 'AAAA' }

const page = overviewOf(MODELS, ASSETS)
const document = new DOMParser().parseFromString(page, 'text/html')

function rowsOf(table: string): string[] {
  return [...document.querySelectorAll(`#${table} tbody tr`)].map((row) =>
    [...row.querySelectorAll('td')].map((cell) => cell.textContent?.trim()).join(' | '),
  )
}

/** A post-it of a made-up wall, its id its slug. */
function sticky(
  type: Sticky['type'],
  context: string,
  slug: string,
  answers: Sticky['answers'] = [],
): Sticky {
  return {
    id: slug,
    slug,
    type,
    label: slug.replace(/^.*:/, ''),
    feature: null,
    name: slug,
    class: null,
    context,
    module: null,
    detail: null,
    answeredBy: [],
    answers,
    readBy: [],
  }
}

function link(from: string, to: string): Link {
  return { from, to, via: null, certain: true, when: null }
}

/** Alpha's wall, which holds each kind of post-it the overview must leave out beside those it lists. */
const ALPHA: Model = {
  context: 'Alpha',
  modules: [],
  stickies: [
    sticky('event', 'Alpha', 'published:Zeta'),
    sticky('event', 'Alpha', 'published:Eta'),
    sticky('policy', 'Beta', 'Beta/policy:Hear'),
    sticky('policy', 'Alpha', 'policy:Own'),
    sticky('event', 'Beta', 'Beta/published:Theirs'),
    sticky('command', 'Alpha', 'command:Send'),
    sticky('externalSystem', 'Alpha', 'externalSystem:Zebras', [
      { context: 'Beta', slug: 'readModel:Z', label: 'Z' },
    ]),
    sticky('externalSystem', 'Alpha', 'externalSystem:Ants', [
      { context: 'Beta', slug: 'readModel:A', label: 'A' },
      { context: null, slug: null, label: 'Nowhere' },
    ]),
    sticky('externalSystem', 'Beta', 'Beta/externalSystem:Theirs', [
      { context: 'Alpha', slug: 'readModel:T', label: 'T' },
    ]),
    sticky('readModel', 'Alpha', 'readModel:View', [{ context: 'Beta', slug: 'readModel:V', label: 'V' }]),
  ],
  links: [
    link('published:Zeta', 'Beta/policy:Hear'),
    link('published:Eta', 'Beta/policy:Hear'),
    link('published:Eta', 'policy:Own'),
    link('Beta/published:Theirs', 'Beta/policy:Hear'),
    link('command:Send', 'Beta/policy:Hear'),
  ],
}

describe('the overview of a made-up wall', () => {
  const made = new DOMParser().parseFromString(overviewOf([ALPHA], ASSETS), 'text/html')
  const rows = (table: string): string[] =>
    [...made.querySelectorAll(`#${table} tbody tr`)].map((row) =>
      [...row.querySelectorAll('td')].map((cell) => cell.textContent?.trim()).join(' | '),
    )

  it('lists only the events of the wall that a policy of another context hears, by their label', () => {
    expect(rows('events')).toEqual(['Alpha | Eta | Beta | Hear', 'Alpha | Zeta | Beta | Hear'])
  })

  it('lists only the ports of the wall that a service of another context answers, by their label', () => {
    expect(rows('services')).toEqual(['Alpha | Ants | Beta | A', 'Alpha | Zebras | Beta | Z'])
  })
})

describe('the overview of the contexts', () => {
  it('leads from each context to its wall', () => {
    expect(
      [...document.querySelectorAll<HTMLAnchorElement>('#contexts a.wall')].map((link) => [
        link.textContent?.trim(),
        link.getAttribute('href'),
      ]),
    ).toEqual([
      ['Accounts', 'accounts/'],
      ['Booking', 'booking/'],
      ['Campaigns', 'campaigns/'],
      ['Connectors', 'connectors/'],
      ['Personal data', 'personal-data/'],
      ['Prospecting', 'prospecting/'],
      ['Reporting', 'reporting/'],
    ])
  })

  it('lists the events a context publishes that the policies of another one hear', () => {
    expect(rowsOf('events')).toContain('Booking | Slot booked | Personal data | Identify booker')
  })

  it('lists the published services the ports of a context ask of another one', () => {
    expect(rowsOf('services')).toContain('Booking | Hosts | Accounts | Members')
  })

  it('links each event to its post-it on the wall that publishes it', () => {
    const link = [...document.querySelectorAll<HTMLAnchorElement>('#events a')].find(
      (candidate) => candidate.textContent === 'Slot booked',
    )

    expect(link?.getAttribute('href')).toBe('booking/#at=published%3ASlotBooked')
  })

  it('draws each context', () => {
    expect(document.querySelectorAll('#map .context').length).toBe(7)
  })

  it('draws an arrow from a context to another one whose policies hear its events', () => {
    expect(document.querySelector('#map .relation.events title')?.textContent).toMatch(
      /^\w[\w ]* publishes to \w[\w ]*: \d+$/,
    )
  })

  it('draws an arrow from a context to another one whose published services it asks', () => {
    expect(document.querySelector('#map .relation.services title')?.textContent).toMatch(
      /^\w[\w ]* asks \w[\w ]*: \d+$/,
    )
  })

  it('loads nothing from outside', () => {
    expect(document.querySelectorAll('link, script[src], img[src^="http"]').length).toBe(0)
  })

  it('writes the page the repository holds, which make event-storming wrote', () => {
    const font = readFileSync('event-storming/font/patrick-hand.woff2').toString('base64')

    expect(overviewOf(MODELS, { style: '', font })).toBe(readFileSync(`${WALLS}/index.html`, 'utf8'))
  })

  it('writes the same page from the same models, whatever their order', () => {
    expect(overviewOf([...MODELS].reverse(), ASSETS)).toBe(page)
  })
})
