import { useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api } from '@/shared/api/client'
import {
  type OrganizationTimeline,
  organizationTimelineQuery,
  type Timeline,
  timelineKeys,
  timelineQuery,
} from './queries'

/**
 * The API answers the first slice of the timeline with the new exchange: the timeline starts again from it,
 * and those of its organizations will be read again.
 */
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
      void queryClient.invalidateQueries({ queryKey: timelineKeys.ofOrganizations })
      toast.success(t('exchanges.noted'))
    },
  })
}

/**
 * Noted from the card of an organization: its timeline starts again from the first slice the API answers; the
 * other timelines, of its contacts and of their other organizations, will be read again when shown.
 */
export function useNoteOrganizationExchange() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/organizations/{id}/exchanges', {
    meta: { silentValidation: true },
    onSuccess: (timeline: OrganizationTimeline, { params }) => {
      void queryClient.invalidateQueries({ queryKey: timelineKeys.ofContacts, refetchType: 'none' })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.ofOrganizations, refetchType: 'none' })
      queryClient.setQueryData(organizationTimelineQuery(params.path.id).queryKey, {
        pages: [timeline],
        pageParams: [undefined],
      })
      toast.success(t('exchanges.noted'))
    },
  })
}
