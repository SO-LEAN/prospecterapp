import { readFileSync } from 'node:fs'
import type { TestProject } from 'vitest/node'
import { gridOf } from '../grid.ts'
import type { Model } from '../model.ts'
import { pageOf } from '../page.ts'
import { dataOf, type WallData } from '../page-data.ts'
import { wallOf } from '../wall.ts'

/** A wall's page and the data its script takes, as the generator writes them. */
export interface ScriptedWall {
  page: string
  data: WallData
}

declare module 'vitest' {
  export interface ProvidedContext {
    booking: ScriptedWall
    prospecting: ScriptedWall
  }
}

/**
 * The walls the tests of the page's script open, built once in Vitest's main process, before any test file loads: those
 * tests judge the script, and the generator's own tests judge the walls. Built while a test file loads, every line of
 * the generator would run outside any test, and the mutation would run all the tests against each of its mutants.
 */
export default function provideTheScriptedWalls(project: TestProject): void {
  project.provide('booking', scriptedWallOf('booking'))
  project.provide('prospecting', scriptedWallOf('prospecting'))
}

function scriptedWallOf(context: string): ScriptedWall {
  const model = JSON.parse(readFileSync(`public/docs/event-storming/${context}/model.json`, 'utf8')) as Model
  const wall = wallOf(model)
  return {
    page: pageOf(model, { style: '', font: '' }),
    data: dataOf(wall, { roomy: gridOf(wall, 'roomy'), compact: gridOf(wall, 'compact') }),
  }
}
