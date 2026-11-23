import { type Mutation, MutationCache, type Query, QueryCache, QueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { isProblem, messageOf } from '@/shared/api/problem'
import { i18n } from '@/shared/i18n'

const isClientError = (error: unknown) => isProblem(error) && error.status < 500

const shouldRetry = (failureCount: number, error: unknown) => !isClientError(error) && failureCount < 2

const stillShowsCachedData = (query: Query<unknown, unknown>) => query.state.data !== undefined

/** The problems a form shows itself (`applyViolations`): its fields' validation errors and the role's refusal. */
const isWordedByAForm = (error: unknown) => isProblem(error) && (error.status === 422 || error.status === 403)

const isShownByItsForm = (error: unknown, mutation: Mutation<unknown, unknown, unknown, unknown>) => {
  const meta = mutation.options.meta
  return (
    (meta?.silentValidation === true && isWordedByAForm(error)) ||
    (meta?.silentClientErrors === true && isClientError(error))
  )
}

/** A toast shows a mutation's refusal or failure, unless the mutation's form shows it. */
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
 * The server state cache. Its defaults keep the UI fast. Data stays fresh for 30 s, so a mount does not refetch it.
 * The cache keeps data for 5 min after its last use. A window focus refetches it in the background.
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
