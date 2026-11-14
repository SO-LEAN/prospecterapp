import { type QueryClient, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { fetchClient } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import { dataOf } from '@/shared/api/slices'
import { readTheTimelinesAgain, UNDO_DELAY_MS } from '../mutations'
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

/**
 * An exchange dragged or stretched in the calendar: shown at once where it was dropped, set back where it was when
 * the API refuses, the refusal told by the toast of every mutation (`query-client.ts`); once moved, its toast offers
 * to move it back. What follows is in the options of the mutation, the toast outliving any component.
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
    onError: (_error, _moved, context) => {
      for (const [queryKey, calendar] of context?.before ?? []) queryClient.setQueryData(queryKey, calendar)
    },
    onSuccess: (exchange, { item, undoing }) => {
      queryClient.setQueryData(exchangeQuery(exchange.id).queryKey, exchange)
      readTheTimelinesAgain(queryClient)
      if (undoing) {
        toast.success(t('calendar.moveUndone'))
        return
      }
      toast.success(t('exchanges.handling.moved'), {
        duration: UNDO_DELAY_MS,
        action: { label: t('common.undo'), onClick: () => move.mutate(backWhereItWas(item)) },
      })
    },
  })

  return move
}

function backWhereItWas({ exchange, ...item }: CalendarExchange): CalendarMove {
  return {
    item: { exchange, ...item },
    occurredAt: new Date(exchange.occurredAt),
    durationInMinutes: exchange.durationInMinutes ?? null,
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
