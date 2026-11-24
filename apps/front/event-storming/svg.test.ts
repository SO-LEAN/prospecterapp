import { existsSync, readdirSync, readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { describe, expect, it } from 'vitest'
import type { Model } from './model.ts'
import { svgsOf } from './svg.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model

const svgs = svgsOf(BOOKING, 'AAAA')

/**
 * The repository's folder of the walls' SVGs, found up from where the tests run: the front's folder, or a copy of it
 * deeper down, as Stryker makes one.
 */
function svgFolder(from = process.cwd()): string {
  const folder = join(from, 'docs/event-storming')
  if (existsSync(folder)) return folder
  if (dirname(from) === from) throw new Error('No docs/event-storming above the tests')
  return svgFolder(dirname(from))
}

describe('the SVGs of a wall', () => {
  it('draws the whole wall and each of its lanes', () => {
    expect(Object.keys(svgs)).toEqual(['wall.svg', 'booking-page.svg', 'public-link.svg', 'appointment.svg'])
  })

  it("draws in a lane's SVG the post-its of that lane only", () => {
    const labels = [...parsed(svgs['public-link.svg']).querySelectorAll('text')].map(
      (text) => text.textContent,
    )

    expect([labels.includes('Book slot'), labels.includes('Open booking')]).toEqual([true, false])
  })

  it('holds its handwriting, so that GitHub shows it as drawn', () => {
    expect(parsed(svgs['wall.svg']).querySelector('style')?.textContent).toContain(
      'url(data:font/woff2;base64,AAAA)',
    )
  })

  for (const folder of readdirSync(svgFolder())) {
    it(`draws the wall of ${folder} the repository holds, which make event-storming wrote`, () => {
      const model = JSON.parse(
        readFileSync(`public/docs/event-storming/${folder}/model.json`, 'utf8'),
      ) as Model
      const font = readFileSync('event-storming/font/patrick-hand.woff2').toString('base64')

      expect(svgsOf(model, font)['wall.svg']).toBe(
        readFileSync(join(svgFolder(), folder, 'wall.svg'), 'utf8'),
      )
    })
  }

  it('draws the same SVGs from the same model', () => {
    expect(svgsOf(BOOKING, 'AAAA')).toEqual(svgs)
  })
})

function parsed(svg: string | undefined): Document {
  return new DOMParser().parseFromString(svg ?? '', 'image/svg+xml')
}
