import { readdirSync, readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'
import type { Model } from './model.ts'
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

  it('writes the same page from the same models, whatever their order', () => {
    expect(overviewOf([...MODELS].reverse(), ASSETS)).toBe(page)
  })
})
