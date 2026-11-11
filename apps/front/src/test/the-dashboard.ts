import type { QueryClient } from '@tanstack/react-query'
import { vi } from 'vitest'
import { queryKeys } from '@/shared/api/keys'
import { WHILE_THE_REACTIONS_RUN_MS } from '@/shared/api/reactions'

/**
 * The dashboard read before a gesture, its figures counted by the reactions to it a moment later: tells whether the
 * gesture has it read again once they may have run.
 */
export function theDashboardInTheCacheOf(queryClient: QueryClient) {
  queryClient.setQueryData(queryKeys.dashboard, { stats: {} })

  return {
    async isReadAgainAfterTheReactions() {
      queryClient.setQueryData(queryKeys.dashboard, { stats: {} })
      await vi.advanceTimersByTimeAsync(WHILE_THE_REACTIONS_RUN_MS[0])
      return queryClient.getQueryState(queryKeys.dashboard)?.isInvalidated
    },
  }
}
