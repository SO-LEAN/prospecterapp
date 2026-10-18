import { defineConfig, devices } from '@playwright/test'

/** End-to-end journeys against the running local stack (`make start`), real sign-in on Cognito (MiniStack) included. */
export default defineConfig({
  testDir: './e2e',
  globalSetup: './e2e/wait-for-the-stack.ts',
  timeout: 30_000,
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: 'https://local.prospecter.io',
    ignoreHTTPSErrors: true,
    locale: 'fr-FR',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
})
