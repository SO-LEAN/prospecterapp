import { spawnSync } from 'node:child_process'
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'

const BIOME = join(import.meta.dirname, '../node_modules/.bin/biome')
const THE_RULE = join(import.meta.dirname, 'dates-written-once.grit')
const ONLY_THE_RULE = { linter: { rules: { recommended: false } }, plugins: [THE_RULE] }
const THE_MESSAGE =
  'A date or an hour shown comes from shared/lib/format.ts, one function for each case (docs/architecture/front.md).'
const ANNOTATION = /^::error title=plugin,file=([^,]+),line=(\d+),[^:]*::(.*)$/

type Refusal = { file: string; line: number; message: string }

/** Lints a module with the rule alone, in a project of its own: what the rule refuses, by line. */
function refusalsOf(module: string): Refusal[] {
  const project = mkdtempSync(join(tmpdir(), 'dates-written-once-'))
  try {
    writeFileSync(join(project, 'biome.json'), JSON.stringify(ONLY_THE_RULE))
    writeFileSync(join(project, 'moment.ts'), module)
    const lint = spawnSync(BIOME, ['lint', '--reporter=github', 'moment.ts'], {
      cwd: project,
      encoding: 'utf8',
    })
    return lint.stdout
      .split('\n')
      .map((line) => ANNOTATION.exec(line))
      .filter((annotation) => annotation !== null)
      .map(([, file, line, message]) => ({ file: file ?? '', line: Number(line), message: message ?? '' }))
  } finally {
    rmSync(project, { recursive: true, force: true })
  }
}

function linesRefused(module: string): number[] {
  return refusalsOf(module).map((refusal) => refusal.line)
}

describe('the rule of the dates written once', () => {
  it('refuses a date written outside shared/lib/format.ts, naming its file, its line and the rule', () => {
    const [refusal] = refusalsOf(
      `const dayFormat = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' })\n`,
    )

    expect(refusal).toEqual({
      file: expect.stringMatching(/\/moment\.ts$/),
      line: 1,
      message: THE_MESSAGE,
    })
  })

  it('refuses every way of writing a date or an hour', () => {
    const module = [
      `const day = new Intl.DateTimeFormat('fr-FR')`,
      `const later = new Intl.RelativeTimeFormat('fr-FR')`,
      `const shown = moment.toLocaleDateString('fr-FR')`,
      `const hour = moment.toLocaleTimeString()`,
      `const both = moment.toLocaleString('fr-FR')`,
      `const plain = Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' }).format(moment)`,
    ].join('\n')

    expect(linesRefused(module)).toEqual([1, 2, 3, 4, 5, 6])
  })

  it('accepts reading the time zone of the browser and writing a number', () => {
    const module = [
      `const zone = Intl.DateTimeFormat().resolvedOptions().timeZone`,
      `const count = new Intl.NumberFormat('fr-FR').format(3)`,
    ].join('\n')

    expect(refusalsOf(module)).toEqual([])
  })
})
