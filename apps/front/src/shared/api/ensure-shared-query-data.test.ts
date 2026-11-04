import { CancelledError, QueryClient, QueryObserver, queryOptions } from '@tanstack/react-query'
import { describe, expect, it } from 'vitest'
import { ensureSharedQueryData } from './ensure-shared-query-data'

const ACCOUNT = { name: 'Paul' }

function anAccountRequestAnswering() {
  const answers: Array<(account: typeof ACCOUNT) => void> = []
  const options = queryOptions({
    queryKey: ['account'],
    queryFn: ({ signal }) =>
      new Promise<typeof ACCOUNT>((resolve, reject) => {
        signal.addEventListener('abort', () => reject(signal.reason))
        answers.push(resolve)
      }),
  })
  const answer = () => {
    for (const resolve of answers) {
      resolve(ACCOUNT)
    }
  }

  return { options, answer }
}

/** What StrictMode does to a component reading the query: it subscribes, leaves, then subscribes again. */
function aComponentMountedTwice(
  queryClient: QueryClient,
  options: ReturnType<typeof anAccountRequestAnswering>['options'],
) {
  const observer = new QueryObserver(queryClient, options)
  observer.subscribe(() => {})()
  observer.subscribe(() => {})
}

describe('ensureSharedQueryData', () => {
  it('gives the data of the query', async () => {
    const queryClient = new QueryClient()
    const { options, answer } = anAccountRequestAnswering()

    const loaded = ensureSharedQueryData(queryClient, options)
    answer()

    await expect(loaded).resolves.toEqual(ACCOUNT)
  })

  it('gives the data once the request started again, when the only component reading it left while it ran', async () => {
    const queryClient = new QueryClient()
    const { options, answer } = anAccountRequestAnswering()

    const loaded = ensureSharedQueryData(queryClient, options)
    aComponentMountedTwice(queryClient, options)
    await Promise.resolve()
    answer()

    await expect(loaded).resolves.toEqual(ACCOUNT)
  })

  it('asks no more once the queries are cleared while it runs, as signing out does', async () => {
    const queryClient = new QueryClient()
    const { options, answer } = anAccountRequestAnswering()

    const loaded = ensureSharedQueryData(queryClient, options)
    queryClient.clear()
    await Promise.resolve()
    answer()

    await expect(loaded).rejects.toBeInstanceOf(CancelledError)
  })

  it('fails as the request fails', async () => {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
    const refused = new Error('refused')

    await expect(
      ensureSharedQueryData(queryClient, { queryKey: ['account'], queryFn: () => Promise.reject(refused) }),
    ).rejects.toBe(refused)
  })
})
