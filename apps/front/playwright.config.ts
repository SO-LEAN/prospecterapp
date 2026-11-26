import { defineConfig, devices } from '@playwright/test'
import { PUBLIC_HOST, SPA_URL, WORKERS } from './e2e/stack'

/**
 * The journeys that run once the others are done, one after the other (ADR 0117, 0122, 0136): the main journey signs
 * the demonstration member out, everywhere (Cognito global sign-out), and beside another journey outlasted its minute;
 * the dashboard, after it, reads a figure that the worker records live, in the account the console opened for the run
 * (the demonstration's figures are counted when it is given back, before any journey).
 */
const MAIN_JOURNEY = /\/journey\.spec\.ts$/
const DASHBOARD = /\/dashboard-figures\.spec\.ts$/
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
/**
 * Chromium resolves the names in `.localhost` to its own loopback, whatever /etc/hosts says: in the network of the
 * stack, they go to its nginx, whose address `make e2e` gives (STACK_ADDRESS).
 */
const STACK_ADDRESS = process.env.STACK_ADDRESS
const HOSTS_TO_THE_STACK = STACK_ADDRESS
  ? [`--host-resolver-rules=MAP ${PUBLIC_HOST} ${STACK_ADDRESS}, MAP *.${PUBLIC_HOST} ${STACK_ADDRESS}`]
  : []
const CHROME = { ...devices['Desktop Chrome'], launchOptions: { args: HOSTS_TO_THE_STACK } }
/**
 * The merge queue keeps the video of a journey that failed (ADR 0220): no one watches the runner. On the machine, the
 * trace is enough, and recording every journey would take processors from the other checkouts.
 */
const VIDEO = process.env.CI ? 'retain-on-failure' : 'off'

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
    video: VIDEO,
  },
  projects: [
    { name: 'together-longest', use: CHROME, testMatch: LONGEST },
    { name: 'together', use: CHROME, testIgnore: [MAIN_JOURNEY, DASHBOARD, LONGEST] },
    {
      name: 'alone',
      use: CHROME,
      testMatch: MAIN_JOURNEY,
      dependencies: ['together-longest', 'together'],
    },
    { name: 'alone-dashboard', use: CHROME, testMatch: DASHBOARD, dependencies: ['alone'] },
  ],
})
