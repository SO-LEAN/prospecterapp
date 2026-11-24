import { spawnSync } from 'node:child_process'
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'

const BIOME = join(import.meta.dirname, '../node_modules/.bin/biome')
const THE_RULE = join(import.meta.dirname, 'french-spacing.grit')
const ONLY_THE_RULE = { linter: { rules: { recommended: false } }, plugins: [THE_RULE] }
const NO_BREAK_SPACE = '\u00a0'
const NARROW_NO_BREAK_SPACE = '\u202f'
/** A rule that refuses its own fix makes Biome apply it again and again. The run stops after this long, so that its test fails instead of hanging. */
const BIOME_RUN_LIMIT_MS = 15_000
const ANNOTATION = /^::error title=plugin,file=([^,]+),line=(\d+),[^:]*::(.*)$/

type Refusal = { file: string; line: number; message: string }

/** Runs Biome with the rule alone on labels, in a project of its own: what it prints, and the labels it leaves. */
function lintAlone(labels: string, ...options: string[]): { output: string; labels: string } {
  const project = mkdtempSync(join(tmpdir(), 'french-spacing-'))
  try {
    writeFileSync(join(project, 'biome.json'), JSON.stringify(ONLY_THE_RULE))
    writeFileSync(join(project, 'fr.ts'), labels)
    const lint = spawnSync(BIOME, ['lint', ...options, 'fr.ts'], {
      cwd: project,
      encoding: 'utf8',
      timeout: BIOME_RUN_LIMIT_MS,
    })
    return { output: lint.stdout, labels: readFileSync(join(project, 'fr.ts'), 'utf8') }
  } finally {
    rmSync(project, { recursive: true, force: true })
  }
}

/** What the rule refuses in labels, by line. */
function refusalsOf(labels: string): Refusal[] {
  return lintAlone(labels, '--reporter=github')
    .output.split('\n')
    .map((line) => ANNOTATION.exec(line))
    .filter((annotation) => annotation !== null)
    .map(([, file, line, message]) => ({ file: file ?? '', line: Number(line), message: message ?? '' }))
}

/** The labels once `make fix` has fixed them. */
function fixed(labels: string): string {
  return lintAlone(labels, '--write').labels
}

function labelsOf(...labels: string[]): string {
  return `export const fr = {\n${labels.map((label, index) => `  label${index}: '${label}',\n`).join('')}}\n`
}

describe('the rule of the spaces of the French labels', () => {
  it('refuses a space before a colon, naming its line and the no-break space it takes', () => {
    const [refusal] = refusalsOf(`export const fr = {\n  name: 'Nom :',\n}\n`)

    expect(refusal).toEqual({
      file: expect.stringMatching(/\/fr\.ts$/),
      line: 2,
      message: expect.stringContaining('no-break space (U+00A0) before a colon'),
    })
  })

  it('puts a no-break space before a colon, changing nothing else', () => {
    expect(fixed(labelsOf('Nom :', 'Prénom'))).toBe(labelsOf(`Nom${NO_BREAK_SPACE}:`, 'Prénom'))
  })

  it('puts a no-break space before each colon of a label', () => {
    expect(fixed(labelsOf('Du : {{start}}, au : {{end}}'))).toBe(
      labelsOf(`Du${NO_BREAK_SPACE}: {{start}}, au${NO_BREAK_SPACE}: {{end}}`),
    )
  })

  it.each([';', '?', '!'])('refuses a space before %s, naming the narrow no-break space it takes', (mark) => {
    expect(refusalsOf(labelsOf(`Oui ${mark}`))).toEqual([
      {
        file: expect.stringMatching(/\/fr\.ts$/),
        line: 2,
        message: expect.stringContaining(
          'narrow no-break space (U+202F) before a semicolon, a question mark or an exclamation mark',
        ),
      },
    ])
  })

  it.each([';', '?', '!'])('puts a narrow no-break space before %s', (mark) => {
    expect(fixed(labelsOf(`Oui ${mark}`))).toBe(labelsOf(`Oui${NARROW_NO_BREAK_SPACE}${mark}`))
  })

  const BADLY_QUOTED = [
    ['an opening mark followed by a space', `« Membre${NO_BREAK_SPACE}»`],
    ['an opening mark followed by no space', `«Membre${NO_BREAK_SPACE}»`],
    ['an opening mark followed by two spaces', `«  Membre${NO_BREAK_SPACE}»`],
    ['a closing mark after a space', `«${NO_BREAK_SPACE}Membre »`],
    ['a closing mark after no space', `«${NO_BREAK_SPACE}Membre»`],
    ['a closing mark after two spaces', `«${NO_BREAK_SPACE}Membre  »`],
  ]

  it.each(BADLY_QUOTED)('refuses %s, naming the no-break space it takes', (_, label) => {
    expect(refusalsOf(labelsOf(label))).toEqual([
      {
        file: expect.stringMatching(/\/fr\.ts$/),
        line: 2,
        message: expect.stringContaining('no-break space (U+00A0) inside French quotation marks'),
      },
    ])
  })

  it.each(BADLY_QUOTED)('puts a no-break space by %s', (_, label) => {
    expect(fixed(labelsOf(label))).toBe(labelsOf(`«${NO_BREAK_SPACE}Membre${NO_BREAK_SPACE}»`))
  })

  it.each([
    ['a colon after a no-break space', `Nom${NO_BREAK_SPACE}:`],
    ['a semicolon after a narrow no-break space', `Oui${NARROW_NO_BREAK_SPACE}; non`],
    ['a question mark after a narrow no-break space', `Supprimer${NARROW_NO_BREAK_SPACE}?`],
    ['French quotation marks holding no-break spaces', `«${NO_BREAK_SPACE}Membre${NO_BREAK_SPACE}»`],
    ['a colon with no space before it', 'https://prospecter.localhost'],
    ['a mark with no space before it', '{{count}}!'],
  ])('accepts %s', (_, label) => {
    expect(refusalsOf(labelsOf(label))).toEqual([])
  })
})
