import { execFileSync } from 'node:child_process'
import { describe, expect, it } from 'vitest'

/** The script the CI and make ci ask which checks a branch's changed files need (ADR 0110). */
const CHANGED_CHECKS = '../../make/changed-checks.sh'

function checksOf(changedFile: string): string[] {
  const output = execFileSync('sh', [CHANGED_CHECKS], { input: `${changedFile}\n`, encoding: 'utf8' })
  return output.trim().split('\n')
}

describe('the checks a changed file of the front needs', () => {
  it("runs the front's check, the contract's and the journeys for a file of the SPA, and mutates the SPA", () => {
    expect(checksOf('apps/front/src/app/contact-page.tsx')).toEqual([
      'contract',
      'e2e',
      'front',
      'mutation-front',
      'secrets',
    ])
  })

  it("runs the front's check for a lint rule of the SPA, whose tests the front's check runs", () => {
    expect(checksOf('apps/front/lint/french-spacing.grit')).toEqual(['contract', 'e2e', 'front', 'secrets'])
  })
})
