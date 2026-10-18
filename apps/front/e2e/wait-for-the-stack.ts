import { request } from '@playwright/test'

/** How long the stack may take to answer: a function of MiniStack starting, or rebuilding the cache after a change. */
const PATIENCE_MS = 120_000
const PAUSE_MS = 1_000
/** The SPA asks several resources at once, each in a function of its own: as many are warmed. */
const FUNCTIONS_AT_ONCE = 3

/** What each part answers once it is up: the SPA its page, the api the user pool, the storage a missing size. */
const parts = [
  { name: 'the SPA', url: 'https://local.prospecter.io/', up: (status: number) => status === 200 },
  {
    name: 'the api',
    url: 'https://api.local.prospecter.io/api/user-pool',
    up: (status: number) => status === 200,
  },
  {
    name: 'the storage',
    url: 'https://static.local.prospecter.io/storage/small/none',
    up: (status: number) => status < 500,
  },
]

/**
 * Global setup of the journeys: they start once every part of the stack answers, its functions warm, so that a
 * journey never meets a function still starting after a change of the code.
 */
export default async function waitForTheStack() {
  const client = await request.newContext({ ignoreHTTPSErrors: true })
  const deadline = Date.now() + PATIENCE_MS
  try {
    for (const part of parts) {
      while (!(await answersAtOnce(client, part))) {
        if (Date.now() > deadline) {
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
