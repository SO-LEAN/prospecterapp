import { type Mutation, MutationCache, type Query, QueryCache, QueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { isProblem } from '@/shared/api/problem'
import { i18n } from '@/shared/i18n'

const isClientError = (error: unknown) => isProblem(error) && error.status < 500

const shouldRetry = (failureCount: number, error: unknown) => !isClientError(error) && failureCount < 2

const stillShowsCachedData = (query: Query<unknown, unknown>) => query.state.data !== undefined

const isShownByItsForm = (error: unknown, mutation: Mutation<unknown, unknown, unknown, unknown>) =>
  mutation.options.meta?.silentValidation === true && isProblem(error) && error.status === 422

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
      if (stillShowsCachedData(query)) {
        toast.error(i18n.t('errors.refresh'))
      }
    },
  }),
  mutationCache: new MutationCache({
    onError: (error, _variables, _context, mutation) => {
      if (isShownByItsForm(error, mutation)) {
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
