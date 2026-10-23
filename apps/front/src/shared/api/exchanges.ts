import type { QueryClient } from '@tanstack/react-query'
import { queryKeys } from './keys'

/**
 * A contact who leaves an organization is struck through in its timeline, no longer once back; erased, their
 * exchanges stay there, their name gone, to make conform (ADR 0079): the timelines and the exchanges are read
 * again.
 */
export function readTheExchangesAgain(queryClient: QueryClient) {
  void queryClient.invalidateQueries({ queryKey: queryKeys.organizationTimelines })
  void queryClient.invalidateQueries({ queryKey: queryKeys.exchangesToMakeConform })
  void queryClient.invalidateQueries({ queryKey: queryKeys.exchange })
}
