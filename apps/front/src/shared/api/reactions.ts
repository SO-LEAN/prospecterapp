import { hashKey, type QueryClient, type QueryKey } from '@tanstack/react-query'

/**
 * The reactions to a gesture run after it, in the worker of the API (ADR 0101), a second or so later: what they
 * change is read again at once, then once at this moment, when they ran.
 */
export const AFTER_THE_REACTIONS_MS = 2_500

/** The queries to read again once the reactions to the last gestures ran, by the client caching them. */
const toReadAfterTheReactions = new WeakMap<
  QueryClient,
  { queryKeys: Map<string, QueryKey>; timer: number }
>()

/**
 * The queries showing what the reactions to a gesture change are read again, now and once they ran: only those
 * displayed, the others when they are shown again; each once at each moment, however many times the gesture names
 * it, the gestures following each other read once after the reactions to the last.
 */
export function readAgainAfterTheReactions(queryClient: QueryClient, ...queryKeys: readonly QueryKey[]) {
  readTheDisplayedOnes(queryClient, queryKeys, { cancelRefetch: false })
  readOnceTheReactionsRan(queryClient, queryKeys)
}

/**
 * At once, a query being read already is not read again: its read is the one awaited. Once the reactions ran, a read
 * still under way started before them: it is read anew.
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
