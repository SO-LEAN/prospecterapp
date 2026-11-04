import {
  CancelledError,
  type EnsureQueryDataOptions,
  type QueryClient,
  type QueryKey,
} from '@tanstack/react-query'

/**
 * `ensureQueryData` for a loader whose query a component outside its route reads too (the user menu of the shell
 * reads the account). When that component leaves while the request runs, the last one reading it (StrictMode mounts
 * it twice), TanStack Query cancels the request and reverts the query, then starts it again for the component back:
 * the loader waits for that one, the cancellation is not a failure of the page.
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
