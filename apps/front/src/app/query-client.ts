import { MutationCache, QueryCache, QueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { isProblem } from '@/shared/api/problem'
import { i18n } from '@/shared/i18n'

function shouldRetry(failureCount: number, error: unknown) {
  // client errors (validation, not found, forbidden) will not succeed by retrying
  if (isProblem(error) && error.status < 500) {
    return false
  }
  return failureCount < 2
}

/**
 * Server state cache. Defaults favour a snappy UI: data is fresh for 30 s (no refetch on every
 * mount), kept 5 min after its last use, refetched in background on window focus.
 */
export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      gcTime: 5 * 60_000,
      retry: shouldRetry,
    },
  },
  queryCache: new QueryCache({
    onError: (_error, query) => {
      // a failed background refetch keeps showing the cached data: just warn
      if (query.state.data !== undefined) {
        toast.error(i18n.t('errors.refresh'))
      }
    },
  }),
  mutationCache: new MutationCache({
    onError: (error, _variables, _context, mutation) => {
      // validation errors are displayed by the forms themselves
      if (mutation.options.meta?.silentValidation && isProblem(error) && error.status === 422) {
        return
      }
      toast.error(isProblem(error) ? error.detail || error.title : i18n.t('errors.network'))
    },
  }),
})

declare module '@tanstack/react-query' {
  interface Register {
    mutationMeta: { silentValidation?: boolean }
  }
}
