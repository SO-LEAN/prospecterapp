import { type QueryClient, type QueryKey, QueryObserver } from '@tanstack/react-query'

/** An observer of the query counting how many times it was read, as a screen showing it would. */
function anObserverCountingTheReads(queryClient: QueryClient, queryKey: QueryKey) {
  let reads = 0
  const observer = new QueryObserver(queryClient, {
    queryKey,
    queryFn: () => {
      reads += 1
      return reads
    },
    staleTime: Number.POSITIVE_INFINITY,
  })

  return { show: () => observer.subscribe(() => {}), reads: () => reads }
}

/** A displayed query counting how many times it was read. */
export function aDisplayedQuery(queryClient: QueryClient, queryKey: QueryKey) {
  const query = anObserverCountingTheReads(queryClient, queryKey)
  query.show()

  return query.reads
}

/** A query displayed, then no longer (its screen closed), counting how many times it was read. */
export function aQueryNoLongerDisplayed(queryClient: QueryClient, queryKey: QueryKey) {
  const query = anObserverCountingTheReads(queryClient, queryKey)
  const hide = query.show()

  return {
    hide,
    reads: query.reads,
    isToReadAtItsOpening: () => queryClient.getQueryState(queryKey)?.isInvalidated,
  }
}
