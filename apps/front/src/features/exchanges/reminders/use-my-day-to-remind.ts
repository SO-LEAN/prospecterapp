import { hashKey, useQuery, useQueryClient } from '@tanstack/react-query'
import { useEffect } from 'react'
import { AFTER_THE_REACTIONS_MS } from '@/shared/api/reactions'
import { myDayToRemindQuery } from '../my-day/queries'

/** How often my day is read again, also in a background tab, where the reminders notify. */
export const READ_AGAIN_MS = 5 * 60_000

/** Once a gesture has changed my day, it is read again when the reactions are done, a second after the page's last read. */
export const ONCE_THE_REACTIONS_RAN_MS = AFTER_THE_REACTIONS_MS + 1_000

/**
 * My day for the reminders. It is read at once, then every five minutes, and once after a gesture changes it. That
 * last read waits until the page's reads and the reactions are done. It never runs along with them, because it would
 * delay them: a page once waited for it after stopping prospecting an organization with many exchanges. What the
 * pages read of my day comes at once.
 */
export function useMyDayToRemind(day: Date) {
  const queryClient = useQueryClient()
  const query = myDayToRemindQuery(day)
  const { data } = useQuery(query)
  const ofThisDay = hashKey(query.queryKey)

  useEffect(() => {
    const read = () => void queryClient.prefetchQuery(query)
    let afterTheGesture: number | undefined
    const readOnceTheReactionsRan = () => {
      window.clearTimeout(afterTheGesture)
      afterTheGesture = window.setTimeout(read, ONCE_THE_REACTIONS_RAN_MS)
    }
    const unsubscribe = queryClient.getQueryCache().subscribe((event) => {
      if (
        event.type === 'updated' &&
        event.action.type === 'invalidate' &&
        event.query.queryHash === ofThisDay
      ) {
        readOnceTheReactionsRan()
      }
    })
    read()
    const reading = window.setInterval(read, READ_AGAIN_MS)

    return () => {
      unsubscribe()
      window.clearInterval(reading)
      window.clearTimeout(afterTheGesture)
    }
  }, [queryClient, ofThisDay])

  return data
}
