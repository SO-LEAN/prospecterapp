import { spawnSync } from 'node:child_process'
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'

const BIOME = join(import.meta.dirname, '../node_modules/.bin/biome')
const THE_RULE = join(import.meta.dirname, 'translated-texts.grit')
const ONLY_THE_RULE = { linter: { rules: { recommended: false } }, plugins: [THE_RULE] }
const ANNOTATION = /^::error title=plugin,file=[^,]+,line=(\d+),[^:]*::(.*)$/

type Refusal = { line: number; message: string }

/** Lints a component with the rule alone, in a project of its own: what the rule refuses, by line. */
function refusalsOf(component: string): Refusal[] {
  const project = mkdtempSync(join(tmpdir(), 'translated-texts-'))
  try {
    writeFileSync(join(project, 'biome.json'), JSON.stringify(ONLY_THE_RULE))
    writeFileSync(join(project, 'component.tsx'), component)
    const lint = spawnSync(BIOME, ['lint', '--reporter=github', 'component.tsx'], {
      cwd: project,
      encoding: 'utf8',
    })
    return lint.stdout
      .split('\n')
      .map((line) => ANNOTATION.exec(line))
      .filter((annotation) => annotation !== null)
      .map(([, line, message]) => ({ line: Number(line), message: message ?? '' }))
  } finally {
    rmSync(project, { recursive: true, force: true })
  }
}

function linesRefused(component: string): number[] {
  return refusalsOf(component).map((refusal) => refusal.line)
}

describe('the rule of the translations', () => {
  it('refuses a text written in a component, naming the rule', () => {
    const [refusal] = refusalsOf(`export const Hint = () => <p>JPEG, PNG, WebP</p>\n`)

    expect(refusal).toEqual({ line: 1, message: expect.stringContaining('shared/i18n/fr.ts through t()') })
  })

  it('refuses a text announced to the screen readers or shown in a field', () => {
    const component = [
      `export const Fields = () => (`,
      `  <>`,
      `    <input placeholder="SAS, SARL…" />`,
      `    <button type="button" aria-label="Close toast" />`,
      `    <span title={'Zoom in'} />`,
      `    <img alt="Logo" src="/logo.png" />`,
      `    <Field label={\`Nom\`} />`,
      `  </>`,
      `)`,
    ].join('\n')

    expect(linesRefused(component)).toEqual([3, 4, 5, 6, 7])
  })

  it('refuses a text shown by a condition or a fallback', () => {
    const component = [
      `export const Hint = ({ error, open }: { error?: string; open: boolean }) => (`,
      `  <p title={open ? 'Fermer' : undefined}>`,
      `    {error ?? 'JPEG, PNG, WebP · 2 Mo max'}`,
      `  </p>`,
      `)`,
    ].join('\n')

    expect(linesRefused(component)).toEqual([2, 3])
  })

  it('refuses a notification written in the code', () => {
    const component = [
      `import { toast } from 'sonner'`,
      `export const notify = () => toast('Saved')`,
      `export const warn = () => toast.error('Failed')`,
    ].join('\n')

    expect(linesRefused(component)).toEqual([2, 3])
  })

  it('accepts the texts of the translations, the punctuation and what is never shown', () => {
    const component = [
      `import { toast } from 'sonner'`,
      `export const Hint = ({ t }: { t: (key: string) => string }) => {`,
      `  if (!t) throw new Error('A new password is required')`,
      `  console.error('The API answered no data')`,
      `  toast.success(t('common.saved'))`,
      `  return (`,
      `    <p className="text-xs" data-testid="hint" title={t('map.zoomIn')}>`,
      `      {t('upload.hint')}{' · '}<img alt="" src="/logo.png" />`,
      `    </p>`,
      `  )`,
      `}`,
    ].join('\n')

    expect(refusalsOf(component)).toEqual([])
  })
})
