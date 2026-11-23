import { type QueryClient, useMutation, useQueryClient } from '@tanstack/react-query'
import { useEffect, useRef } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { fetchClient } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import { dataOf } from '@/shared/api/slices'
import { readTheTimelinesAgain } from '../mutations'
import { exchangeQuery } from '../queries'
import { withTheGesture } from './gestures'
import type { CalendarExchange } from './queries'

/** Where a calendar exchange goes, for how long, and whether the toast moves it back where it was. */
export type CalendarMove = {
  item: CalendarExchange
  occurredAt: Date
  durationInMinutes: number | null
  undoing?: boolean
}

type Calendar = { items: CalendarExchange[] }

/** The single toast that offers to move back the last moved exchange. The next move takes its place. */
const LAST_MOVE_TOAST = 'last-move-in-the-calendar'

/** An opened exchange may be moved from its dialog. Moving it back to the earlier moment would silently undo that move. */
export function withdrawTheOfferToMoveBack() {
  toast.dismiss(LAST_MOVE_TOAST)
}

/**
 * Moves an exchange dragged or stretched in the calendar. It shows at once where it was dropped. When the API refuses,
 * it goes back where it was, and the toast of every mutation shows the refusal (`query-client.ts`). Either way, the
 * calendar is read again. Once the exchange is moved, its toast offers to move it back when the API gives the link
 * (`_links.moveBack`, when its former moment is still to come). The offer stays however long the member takes. It
 * ends when another exchange is moved or opened, or when the member leaves the calendar. A move answered after the
 * member left shows its toast without the offer. What follows is in the mutation's options, because the toast
 * outlives any component.
 */
export function useMoveInTheCalendar() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()
  const calendarLeft = useRef(false)

  const move = useMutation({
    mutationFn: async ({ item, occurredAt, durationInMinutes }: CalendarMove) =>
      dataOf(
        await fetchClient.PATCH('/api/exchanges/{id}/moment', {
          params: { path: { id: item.exchange.id } },
          body: { occurredAt: occurredAt.toISOString(), durationInMinutes },
        }),
      ),
    onMutate: async (moved: CalendarMove) => {
      await queryClient.cancelQueries({ queryKey: queryKeys.myCalendar })
      const before = queryClient.getQueriesData<Calendar>({ queryKey: queryKeys.myCalendar })
      showMoved(queryClient, moved)
      return { before }
    },
    onError: (_error, _moved, onMutateResult) => {
      for (const [queryKey, calendar] of onMutateResult?.before ?? [])
        queryClient.setQueryData(queryKey, calendar)
    },
    onSettled: () =>
      Promise.all([
        queryClient.invalidateQueries({ queryKey: queryKeys.myCalendar }),
        queryClient.invalidateQueries({ queryKey: queryKeys.myDay }),
      ]),
    onSuccess: (exchange, { item, undoing }) => {
      queryClient.setQueryData(exchangeQuery(exchange.id).queryKey, exchange)
      readTheTimelinesAgain(queryClient)
      if (undoing) {
        toast.success(t('calendar.moveUndone'))
        return
      }
      if (calendarLeft.current || !exchange._links.moveBack) {
        withdrawTheOfferToMoveBack()
        toast.success(t('exchanges.handling.moved'))
        return
      }
      toast.success(t('exchanges.handling.moved'), {
        id: LAST_MOVE_TOAST,
        duration: Number.POSITIVE_INFINITY,
        action: { label: t('common.undo'), onClick: () => move.mutate(backWhereItWas(item)) },
      })
    },
  })

  useEffect(() => {
    calendarLeft.current = false
    return () => {
      calendarLeft.current = true
      withdrawTheOfferToMoveBack()
    }
  }, [])

  return move
}

function backWhereItWas(item: CalendarExchange): CalendarMove {
  return {
    item,
    occurredAt: new Date(item.exchange.occurredAt),
    durationInMinutes: item.exchange.durationInMinutes ?? null,
    undoing: true,
  }
}

function showMoved(queryClient: QueryClient, { item, occurredAt, durationInMinutes }: CalendarMove) {
  queryClient.setQueriesData<Calendar>(
    { queryKey: queryKeys.myCalendar },
    (calendar) =>
      calendar && {
        ...calendar,
        items: withTheGesture(calendar.items, { id: item.exchange.id, occurredAt, durationInMinutes }),
      },
  )
}
