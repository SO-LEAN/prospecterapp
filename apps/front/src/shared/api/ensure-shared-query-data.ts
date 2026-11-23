import {
  CancelledError,
  type EnsureQueryDataOptions,
  type QueryClient,
  type QueryKey,
} from '@tanstack/react-query'

/**
 * `ensureQueryData` for a loader whose query a component outside its route also reads (the shell's user menu reads
 * the account). That component can leave while the request runs, as the query's last reader (StrictMode mounts it
 * twice). TanStack Query then cancels the request, reverts the query, and starts it again when the component comes
 * back. The loader waits for that new request, so the cancellation is not a page failure.
 */
export function ensureSharedQueryData<TQueryFnData, TError, TData, TQueryKey extends QueryKey>(
  queryClient: QueryClient,
  options: EnsureQueryDataOptions<TQueryFnData, TError, TData, TQueryKey>,
): Promise<TData> {
  return queryClient
    .ensureQueryData(options)
    .catch((error: unknown) =>
      isLeftByItsReaders(error) ? ensureSharedQueryData(queryClient, options) : Promise.reject(error),
    )
}

function isLeftByItsReaders(error: unknown): boolean {
  return error instanceof CancelledError && error.revert === true
}
