import { useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api } from '@/shared/api/client'
import { type Timeline, timelineQuery } from './queries'

/** The API answers the first slice of the timeline with the new exchange: the timeline starts again from it. */
export function useNoteExchange() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/contacts/{id}/exchanges', {
    meta: { silentValidation: true },
    onSuccess: (timeline: Timeline, { params }) => {
      queryClient.setQueryData(timelineQuery(params.path.id).queryKey, {
        pages: [timeline],
        pageParams: [undefined],
      })
      toast.success(t('exchanges.noted'))
    },
  })
}
