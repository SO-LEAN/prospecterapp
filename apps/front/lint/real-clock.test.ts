import { spawnSync } from 'node:child_process'
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'

const BIOME = join(import.meta.dirname, '../node_modules/.bin/biome')
const THE_RULE = join(import.meta.dirname, 'real-clock.grit')
const ONLY_THE_RULE = { linter: { rules: { recommended: false } }, plugins: [THE_RULE] }
const ANNOTATION = /^::error title=plugin,file=([^,]+),line=(\d+),[^:]*::(.*)$/

type Refusal = { file: string; line: number; message: string }

/** Lints a test with the rule alone, in a project of its own: what the rule refuses, by line. */
function refusalsOf(test: string): Refusal[] {
  const project = mkdtempSync(join(tmpdir(), 'real-clock-'))
  try {
    writeFileSync(join(project, 'biome.json'), JSON.stringify(ONLY_THE_RULE))
    writeFileSync(join(project, 'moment.test.ts'), test)
    const lint = spawnSync(BIOME, ['lint', '--reporter=github', 'moment.test.ts'], { cwd: project, encoding: 'utf8' })
    return lint.stdout
      .split('\n')
      .map((line) => ANNOTATION.exec(line))
      .filter((annotation) => annotation !== null)
      .map(([, file, line, message]) => ({ file: file ?? '', line: Number(line), message: message ?? '' }))
  } finally {
    rmSync(project, { recursive: true, force: true })
  }
}

function linesRefused(test: string): number[] {
  return refusalsOf(test).map((refusal) => refusal.line)
}

describe('the rule of the clock of the tests', () => {
  it('refuses a test reading the real clock, naming its file, its line and the rule', () => {
    const [refusal] = refusalsOf(`it('is late', () => expect(isLate(Date.now())).toBe(true))\n`)

    expect(refusal).toEqual({
      file: expect.stringMatching(/\/moment\.test\.ts$/),
      line: 1,
      message: expect.stringContaining('docs/architecture/tests.md'),
    })
  })

  it('refuses every way of reading the real clock', () => {
    const test = [
      `const now = new Date()`,
      `const today = Date.now()`,
      `const later = new Date(Date.now() + 1000)`,
      `vi.useRealTimers()`,
    ].join('\n')

    expect(linesRefused(test)).toEqual([1, 2, 3, 4])
  })

  it('accepts a named moment, a clock of its own and an exception named', () => {
    const test = [
      `const at = new Date('2026-11-17T13:00:00Z')`,
      `const before = new Date(2026, 10, 17, 9, 0)`,
      `vi.setSystemTime(at)`,
      `vi.useFakeTimers({ toFake: ['Date'] })`,
      `const elapsed = performance.now()`,
      `// biome-ignore lint/plugin: a name of the data the journey creates`,
      `const name = \`E2E \${Date.now()}\``,
    ].join('\n')

    expect(refusalsOf(test)).toEqual([])
  })
})
