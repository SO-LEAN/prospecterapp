import type { QueryClient } from '@tanstack/react-query'
import { queryKeys } from './keys'
import { readAgainAfterTheReactions } from './reactions'

/**
 * The organization's timeline strikes through a contact who leaves it, and stops once they come back. When a contact
 * is erased, the gesture's reactions redact their exchanges (ADR 0079): the exchanges stay, without the name, and the
 * upcoming ones are cancelled. So the timelines, the upcoming exchanges, the exchanges, the calendar and the items to
 * process are read again at once, and again once the reactions ran.
 */
export function readTheExchangesAgain(queryClient: QueryClient) {
  readAgainAfterTheReactions(
    queryClient,
    queryKeys.organizationTimelines,
    queryKeys.organizationUpcomingExchanges,
    queryKeys.exchangesToMakeConform,
    queryKeys.exchange,
    queryKeys.myCalendar,
    queryKeys.myDay,
    queryKeys.exchangesToProcess,
  )
}
