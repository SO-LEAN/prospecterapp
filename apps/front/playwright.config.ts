import { defineConfig, devices } from '@playwright/test'
import { SPA_URL, WORKERS } from './e2e/stack'

/**
 * The journeys that run alone, once the others are done (ADR 0117): the main journey signs the demonstration member
 * out, everywhere (Cognito global sign-out), the dashboard counts a figure of the whole account, and stopping to
 * prospect starts tens of reactions at once.
 */
const ALONE = /\/(journey|dashboard-figures|stop-prospecting)\.spec\.ts$/
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
    { name: 'together', use: CHROME, testIgnore: ALONE },
    { name: 'alone', use: CHROME, testMatch: ALONE, dependencies: ['together'], workers: 1 },
  ],
})
