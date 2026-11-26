import { readFileSync } from 'node:fs'
import { beforeEach, describe, expect, it } from 'vitest'
import { gridOf } from './grid.ts'
import type { Model } from './model.ts'
import { dataOf, type WallData } from './page-data.ts'
import { wallOf } from './wall.ts'

/** Booking's model, as the generator last read it from the code. Vitest runs in the front's folder. */
const BOOKING = JSON.parse(readFileSync('public/docs/event-storming/booking/model.json', 'utf8')) as Model

/** The page make event-storming wrote: its script ends on the data it takes, `(runTheWall)(<data>)`. */
const WRITTEN_PAGE = readFileSync('public/docs/event-storming/booking/index.html', 'utf8')
const START_OF_THE_DATA = ')({"context":'

/** Built again in each test, so that the mutation knows which tests run each line of the generator. */
let data: WallData

beforeEach(() => {
  const wall = wallOf(BOOKING)
  data = dataOf(wall, { roomy: gridOf(wall, 'roomy'), compact: gridOf(wall, 'compact') })
})

describe("the data of a wall's script", () => {
  it('hands the script the data of the page the repository holds, which make event-storming wrote', () => {
    expect(data).toEqual(writtenData())
  })
})

function writtenData(): WallData {
  const start = WRITTEN_PAGE.indexOf(START_OF_THE_DATA) + 2
  const end = WRITTEN_PAGE.indexOf(')\n</script>', start)
  return JSON.parse(WRITTEN_PAGE.slice(start, end)) as WallData
}
