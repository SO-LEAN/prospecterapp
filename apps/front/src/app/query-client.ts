import { type Mutation, MutationCache, type Query, QueryCache, QueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { isProblem, messageOf } from '@/shared/api/problem'
import { i18n } from '@/shared/i18n'

const isClientError = (error: unknown) => isProblem(error) && error.status < 500

const shouldRetry = (failureCount: number, error: unknown) => !isClientError(error) && failureCount < 2

const stillShowsCachedData = (query: Query<unknown, unknown>) => query.state.data !== undefined

/** What a form words itself (`applyViolations`): the validation of its fields, and the refusal of the role. */
const isWordedByAForm = (error: unknown) => isProblem(error) && (error.status === 422 || error.status === 403)

const isShownByItsForm = (error: unknown, mutation: Mutation<unknown, unknown, unknown, unknown>) => {
  const meta = mutation.options.meta
  return (
    (meta?.silentValidation === true && isWordedByAForm(error)) ||
    (meta?.silentClientErrors === true && isClientError(error))
  )
}

/** A refusal of a mutation, or its failure, is told in a toast, unless its form shows it. */
export function mutationsToastingTheirRefusals() {
  return new MutationCache({
    onError: (error, _variables, _context, mutation) => {
      if (isShownByItsForm(error, mutation)) {
        return
      }
      toast.error(isProblem(error) ? messageOf(error) : i18n.t('errors.network'))
    },
  })
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
      if (stillShowsCachedData(query)) {
        toast.error(i18n.t('errors.refresh'))
      }
    },
  }),
  mutationCache: mutationsToastingTheirRefusals(),
})

declare module '@tanstack/react-query' {
  interface Register {
    mutationMeta: { silentValidation?: boolean; silentClientErrors?: boolean }
  }
}
