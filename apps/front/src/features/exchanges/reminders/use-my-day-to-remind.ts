import { hashKey, useQuery, useQueryClient } from '@tanstack/react-query'
import { useEffect } from 'react'
import { WHILE_THE_REACTIONS_RUN_MS } from '@/shared/api/reactions'
import { myDayToRemindQuery } from '../my-day/queries'

/** My day is read again that often, in a tab in the background too, where the reminders notify. */
export const READ_AGAIN_MS = 5 * 60_000

/** Once a gesture changed my day, it is read again when its reactions are done, a second after the last read of the page. */
export const ONCE_THE_REACTIONS_RAN_MS = Math.max(...WHILE_THE_REACTIONS_RUN_MS) + 1_000

/**
 * My day for the reminders: read at once, again every five minutes, and once after a gesture changed it, when the
 * reads of the page and the reactions are done, never along with them, which it would delay (a page waited for it
 * after stopping to prospect an organization of many exchanges). What the pages read of it comes at once.
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
