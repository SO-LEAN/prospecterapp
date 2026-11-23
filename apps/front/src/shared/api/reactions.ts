import { hashKey, type QueryClient, type QueryKey } from '@tanstack/react-query'

/**
 * A gesture's reactions run after it, in the API's worker (ADR 0101), a second or so later. What they change is read
 * again at once, then again after this delay, once they ran.
 */
export const AFTER_THE_REACTIONS_MS = 2_500

/** The queries to read again once the last gestures' reactions ran, keyed by the client that caches them. */
const toReadAfterTheReactions = new WeakMap<
  QueryClient,
  { queryKeys: Map<string, QueryKey>; timer: number }
>()

/**
 * Reads again the queries that show what a gesture's reactions change, now and once the reactions ran. Only the
 * displayed queries are read, and the others when they show again. Each query is read once at each moment, however
 * many times the gesture names it. Successive gestures are read once, after the last gesture's reactions.
 */
export function readAgainAfterTheReactions(queryClient: QueryClient, ...queryKeys: readonly QueryKey[]) {
  readTheDisplayedOnes(queryClient, queryKeys, { cancelRefetch: false })
  readOnceTheReactionsRan(queryClient, queryKeys)
}

/**
 * At once, a query that is already being read is not read again: its current read is the one awaited. Once the
 * reactions ran, a read still under way started before them, so the query is read anew.
 */
function readTheDisplayedOnes(
  queryClient: QueryClient,
  queryKeys: Iterable<QueryKey>,
  { cancelRefetch }: { cancelRefetch: boolean },
) {
  for (const queryKey of queryKeys) {
    void queryClient.invalidateQueries({ queryKey, refetchType: 'active' }, { cancelRefetch })
  }
}

function readOnceTheReactionsRan(queryClient: QueryClient, queryKeys: readonly QueryKey[]) {
  const awaiting = toReadAfterTheReactions.get(queryClient)
  window.clearTimeout(awaiting?.timer)
  const toRead = awaiting?.queryKeys ?? new Map<string, QueryKey>()
  for (const queryKey of queryKeys) {
    toRead.set(hashKey(queryKey), queryKey)
  }
  const timer = window.setTimeout(() => {
    toReadAfterTheReactions.delete(queryClient)
    readTheDisplayedOnes(queryClient, toRead.values(), { cancelRefetch: true })
  }, AFTER_THE_REACTIONS_MS)
  toReadAfterTheReactions.set(queryClient, { queryKeys: toRead, timer })
}
