import { type QueryClient, useMutation, useQueryClient } from '@tanstack/react-query'
import { useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { fetchClient } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import { dataOf } from '@/shared/api/slices'
import { readTheTimelinesAgain } from '../mutations'
import { exchangeQuery } from '../queries'
import { withTheGesture } from './gestures'
import type { CalendarExchange } from './queries'

/** Where an exchange of the calendar goes, for how long, and whether it comes back where it was from the toast. */
export type CalendarMove = {
  item: CalendarExchange
  occurredAt: Date
  durationInMinutes: number | null
  undoing?: boolean
}

type Calendar = { items: CalendarExchange[] }

/** The one toast offering to move back the last exchange moved: the next move takes its place. */
const LAST_MOVE_TOAST = 'last-move-in-the-calendar'

/**
 * An exchange dragged or stretched in the calendar: shown at once where it was dropped, set back where it was when
 * the API refuses, the refusal told by the toast of every mutation (`query-client.ts`), the calendar read again
 * either way; once moved, its toast offers to move it back, however long the member takes, until another exchange is
 * moved or the calendar is left. What follows is in the options of the mutation, the toast outliving any component.
 */
export function useMoveInTheCalendar() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

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
    onSettled: () => queryClient.invalidateQueries({ queryKey: queryKeys.myCalendar }),
    onSuccess: (exchange, { item, undoing }) => {
      queryClient.setQueryData(exchangeQuery(exchange.id).queryKey, exchange)
      readTheTimelinesAgain(queryClient)
      if (undoing) {
        toast.success(t('calendar.moveUndone'))
        return
      }
      toast.success(t('exchanges.handling.moved'), {
        id: LAST_MOVE_TOAST,
        duration: Number.POSITIVE_INFINITY,
        action: { label: t('common.undo'), onClick: () => move.mutate(backWhereItWas(item)) },
      })
    },
  })

  useEffect(() => () => void toast.dismiss(LAST_MOVE_TOAST), [])

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
