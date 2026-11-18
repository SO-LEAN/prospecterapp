import { chromium, type FullConfig, request } from '@playwright/test'
import { API_URL, SPA_URL, STATIC_URL, WORKERS } from './stack'

/** How long the stack may take to answer: a function of MiniStack starting, or rebuilding the cache after a change. */
const PATIENCE_MS = 120_000
const PAUSE_MS = 1_000
/**
 * A page of the SPA asks several resources at once, each in a function of its own: the card of an organization the
 * most, itself, its contacts, its to-dos, its exchanges and those to come (ADR 0122), beside what the shell reads on
 * every page: the connections for its banner (ADR 0120), my booking page for its (ADR 0153), my reminders, the count
 * of my exchanges to process in its menu, then my day to remind of it.
 */
const FUNCTIONS_PER_PAGE = 10
/**
 * As many functions are warmed as the journeys running together ask at once: one more would start its container
 * during a journey, longer than the journey waits for its screen.
 */
const FUNCTIONS_AT_ONCE = FUNCTIONS_PER_PAGE * WORKERS

/** What each part answers once it is up: the SPA its page, the api the user pool, the storage a missing size. */
const parts = [
  { name: 'the SPA', url: `${SPA_URL}/`, up: (status: number) => status === 200 },
  {
    name: 'the api',
    url: `${API_URL}/api/user-pool`,
    up: (status: number) => status === 200,
  },
  {
    name: 'the storage',
    url: `${STATIC_URL}/storage/small/none`,
    up: (status: number) => status < 500,
  },
]

/**
 * Global setup of the journeys: they start once every part of the stack answers, its functions warm, and the SPA
 * renders in the browser, so that a journey never meets a function still starting after a change of the code, nor a
 * dev server still transforming the modules of the SPA after a deployment.
 */
export default async function waitForTheStack(config: FullConfig) {
  const deadline = performance.now() + PATIENCE_MS
  await everyPartAnswers(deadline)
  await theSpaRenders(config, deadline)
}

async function everyPartAnswers(deadline: number) {
  const client = await request.newContext({ ignoreHTTPSErrors: true })
  try {
    for (const part of parts) {
      while (!(await answersAtOnce(client, part))) {
        if (performance.now() > deadline) {
          throw new Error(`${part.name} does not answer at ${part.url}: is the stack running (make start)?`)
        }
        await new Promise((resolve) => setTimeout(resolve, PAUSE_MS))
      }
    }
  } finally {
    await client.dispose()
  }
}

async function answersAtOnce(
  client: Awaited<ReturnType<typeof request.newContext>>,
  part: (typeof parts)[number],
): Promise<boolean> {
  const answers = await Promise.all(
    Array.from({ length: FUNCTIONS_AT_ONCE }, () =>
      client.get(part.url, { failOnStatusCode: false }).then(
        (response) => part.up(response.status()),
        () => false,
      ),
    ),
  )
  return answers.every(Boolean)
}

/** The sign-in screen shown in the browser of the journeys, its hosts resolved as theirs (`playwright.config.ts`). */
async function theSpaRenders(config: FullConfig, deadline: number) {
  const browser = await chromium.launch(config.projects[0]?.use.launchOptions)
  try {
    const page = await browser.newPage({ ignoreHTTPSErrors: true, locale: 'fr-FR' })
    const patience = () => ({ timeout: Math.max(deadline - performance.now(), PAUSE_MS) })
    await page.goto(`${SPA_URL}/`, patience())
    await page.getByRole('button', { name: 'Se connecter' }).waitFor(patience())
  } finally {
    await browser.close()
  }
}
