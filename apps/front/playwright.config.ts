import { defineConfig, devices } from '@playwright/test'
import { SPA_URL, WORKERS } from './e2e/stack'

/**
 * The journey that runs alone, once the others are done (ADR 0117, 0122): the main journey signs the demonstration
 * member out, everywhere (Cognito global sign-out).
 */
const ALONE = /\/journey\.spec\.ts$/
/**
 * The longest of the journeys running together, started first, its project listed before theirs (Playwright runs the
 * projects of a phase in the order of the configuration): last, its reactions alone would outlast the others.
 */
const LONGEST = /\/stop-prospecting\.spec\.ts$/
/**
 * Where a run keeps its report and its traces: a folder per run when the projects run one after the other
 * (E2E_RESULTS, check-e2e), so that the second does not erase those of the first.
 */
const RESULTS = process.env.E2E_RESULTS ? `/${process.env.E2E_RESULTS}` : ''
const CHROME = { ...devices['Desktop Chrome'] }

/** End-to-end journeys against the running local stack (`make start`), real sign-in on Cognito (MiniStack) included. */
export default defineConfig({
  testDir: './e2e',
  globalSetup: './e2e/wait-for-the-stack.ts',
  timeout: 30_000,
  retries: 0,
  workers: WORKERS,
  outputDir: `test-results${RESULTS}`,
  reporter: [['dot'], ['html', { open: 'never', outputFolder: `playwright-report${RESULTS}` }]],
  use: {
    baseURL: SPA_URL,
    ignoreHTTPSErrors: true,
    locale: 'fr-FR',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'together-longest', use: CHROME, testMatch: LONGEST },
    { name: 'together', use: CHROME, testIgnore: [ALONE, LONGEST] },
    {
      name: 'alone',
      use: CHROME,
      testMatch: ALONE,
      dependencies: ['together-longest', 'together'],
      workers: 1,
    },
  ],
})
