import type { QueryClient } from '@tanstack/react-query'
import { queryKeys } from './keys'
import { readAgainAfterTheReactions } from './reactions'

/**
 * A contact who leaves an organization is struck through in its timeline, no longer once back; erased, their
 * exchanges stay there, their name gone, to make conform (ADR 0079), those to come cancelled, by the reactions to
 * the gesture: the timelines, the exchanges, the calendar and those to process are read again at once and once they ran.
 */
export function readTheExchangesAgain(queryClient: QueryClient) {
  readAgainAfterTheReactions(
    queryClient,
    queryKeys.organizationTimelines,
    queryKeys.exchangesToMakeConform,
    queryKeys.exchange,
    queryKeys.myCalendar,
    queryKeys.myDay,
    queryKeys.exchangesToProcess,
  )
}
