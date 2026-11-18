import { spawnSync } from 'node:child_process'
import { cpSync, mkdtempSync, readFileSync, rmSync, symlinkSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'

const FRONT = join(import.meta.dirname, '..')
const TSC = join(FRONT, 'node_modules/.bin/tsc')
const LABELS = 'src/shared/i18n/fr.ts'
const START_OF_THE_LABELS = 'export const fr = {\n'
/** The labels the front may still gain before TypeScript stops typing their keys (TS2589). */
const HEADROOM = 100
/** TypeScript checks the whole front twice as slow while `make ci` runs the other checks beside the tests. */
const TIMEOUT_OF_A_WHOLE_TYPECHECK = 120_000

function headroomLabels(count: number): string {
  const labels = Array.from({ length: count }, (_, index) => `    label${index}: 'Libellé {{n}}',\n`)
  return `  headroom: {\n${labels.join('')}  },\n`
}

/** The front copied with `count` labels more, as a project of its own sharing the dependencies. */
function frontWithMoreLabels(count: number): string {
  const project = mkdtempSync(join(tmpdir(), 'labels-headroom-'))
  cpSync(join(FRONT, 'src'), join(project, 'src'), { recursive: true })
  symlinkSync(join(FRONT, 'node_modules'), join(project, 'node_modules'))
  const tsconfig = JSON.parse(readFileSync(join(FRONT, 'tsconfig.json'), 'utf8'))
  writeFileSync(join(project, 'tsconfig.json'), JSON.stringify({ ...tsconfig, include: ['src'] }))
  const labels = readFileSync(join(project, LABELS), 'utf8')
  writeFileSync(
    join(project, LABELS),
    labels.replace(START_OF_THE_LABELS, `${START_OF_THE_LABELS}${headroomLabels(count)}`),
  )
  return project
}

function typeErrorsOf(project: string): string {
  const typecheck = spawnSync(TSC, ['--noEmit', '-p', project], { encoding: 'utf8' })
  return typecheck.stdout + typecheck.stderr
}

describe('the French labels', () => {
  it(
    `keep their keys typed with ${HEADROOM} labels more`,
    () => {
      const project = frontWithMoreLabels(HEADROOM)
      try {
        expect(typeErrorsOf(project)).toBe('')
      } finally {
        rmSync(project, { recursive: true, force: true })
      }
    },
    TIMEOUT_OF_A_WHOLE_TYPECHECK,
  )
})
