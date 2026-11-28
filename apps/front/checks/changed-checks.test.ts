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

  it("runs the front's check and Terraform's for the CloudFront function, whose tests the front's check runs", () => {
    expect(checksOf('apps/front/infra/functions/release.js.tftpl')).toEqual(['front', 'infra', 'secrets'])
  })
})

describe("the checks a change of the event storming's generator needs", () => {
  it("runs the generator's check alone for its code, which the SPA does not import, and mutates the generator", () => {
    expect(checksOf('apps/front/event-storming/story.ts')).toEqual([
      'event-storming',
      'mutation-event-storming',
      'secrets',
    ])
  })

  it("runs the generator's check alone for a wall it wrote, which the SPA does not read", () => {
    expect(checksOf('apps/front/public/docs/event-storming/accounts/model.json')).toEqual([
      'event-storming',
      'secrets',
    ])
  })
})

describe('the checks a change of a file that the SPA and the generator both read needs', () => {
  it.each([
    'apps/front/package.json',
    'apps/front/pnpm-lock.yaml',
    'apps/front/vite.config.ts',
    'apps/front/tsconfig.json',
    'apps/front/biome.json',
    'apps/front/Makefile',
  ])("runs the front's check and the generator's for %s", (sharedFile) => {
    expect(checksOf(sharedFile)).toEqual(['contract', 'e2e', 'event-storming', 'front', 'secrets'])
  })
})
