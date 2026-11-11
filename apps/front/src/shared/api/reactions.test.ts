import { QueryClient, QueryObserver } from '@tanstack/react-query'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { readAgainAfterTheReactions, WHILE_THE_REACTIONS_RUN_MS } from './reactions'

/** A displayed query counting how many times it was read. */
function aDisplayedQuery(queryClient: QueryClient, queryKey: readonly string[]) {
  let reads = 0
  new QueryObserver(queryClient, {
    queryKey,
    queryFn: () => {
      reads += 1
      return reads
    },
    staleTime: Number.POSITIVE_INFINITY,
  }).subscribe(() => {})

  return () => reads
}

describe('readAgainAfterTheReactions', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('reads the queries again at once, then at each moment the reactions may have run', async () => {
    const queryClient = new QueryClient()
    const readsOfTheContacts = aDisplayedQuery(queryClient, ['contacts'])
    await vi.runAllTimersAsync()

    readAgainAfterTheReactions(queryClient, ['contacts'])
    await vi.advanceTimersByTimeAsync(0)
    expect(readsOfTheContacts()).toBe(2)

    const [first, second] = WHILE_THE_REACTIONS_RUN_MS
    await vi.advanceTimersByTimeAsync(first)
    expect(readsOfTheContacts()).toBe(3)
    await vi.advanceTimersByTimeAsync(second - first)
    expect(readsOfTheContacts()).toBe(4)
  })

  it('reads only the queries named', async () => {
    const queryClient = new QueryClient()
    const readsOfTheOrganizations = aDisplayedQuery(queryClient, ['organizations'])
    await vi.runAllTimersAsync()

    readAgainAfterTheReactions(queryClient, ['contacts'])
    await vi.runAllTimersAsync()

    expect(readsOfTheOrganizations()).toBe(1)
  })
})
