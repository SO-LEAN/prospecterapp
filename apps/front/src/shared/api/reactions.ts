import type { QueryClient, QueryKey } from '@tanstack/react-query'

/**
 * The reactions to a gesture run after it, in the worker of the API (ADR 0101), a second or so later: what they
 * change is read again at once, then at these moments, while they run.
 */
export const WHILE_THE_REACTIONS_RUN_MS = [1_000, 2_500] as const

/** The queries showing what the reactions to a gesture change are read again, now and while they run. */
export function readAgainAfterTheReactions(queryClient: QueryClient, ...queryKeys: readonly QueryKey[]) {
  const readAgain = () => {
    for (const queryKey of queryKeys) {
      void queryClient.invalidateQueries({ queryKey })
    }
  }

  readAgain()
  for (const moment of WHILE_THE_REACTIONS_RUN_MS) {
    setTimeout(readAgain, moment)
  }
}
