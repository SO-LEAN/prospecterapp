import { type QueryClient, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api, fetchClient } from '@/shared/api/client'
import { dataOf } from '@/shared/api/slices'
import { attachFile } from './attachments'
import {
  type ExchangeDetail,
  type ExchangeInput,
  exchangeQuery,
  type OrganizationExchangeInput,
  organizationTimelineQuery,
  timelineKeys,
  timelineQuery,
} from './queries'

/** The new exchange, whose address the API answers in `Location`: files are joined to it. */
const notedIn = (response: Response) => response.headers.get('Location')?.split('/').pop() ?? ''

/**
 * The API answers the first slice of the timeline with the new exchange: the timeline starts again from it,
 * and those of its organizations will be read again.
 */
export function useNoteExchange() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return useMutation({
    meta: { silentValidation: true },
    mutationFn: async ({ contactId, body }: { contactId: string; body: ExchangeInput }) => {
      const { data, error, response } = await fetchClient.POST('/api/contacts/{id}/exchanges', {
        params: { path: { id: contactId } },
        body,
      })
      return { timeline: dataOf({ data, error }), noted: notedIn(response) }
    },
    onSuccess: ({ timeline }, { contactId }) => {
      queryClient.setQueryData(timelineQuery(contactId).queryKey, {
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

  return useMutation({
    meta: { silentValidation: true },
    mutationFn: async ({
      organizationId,
      body,
    }: {
      organizationId: string
      body: OrganizationExchangeInput
    }) => {
      const { data, error, response } = await fetchClient.POST('/api/organizations/{id}/exchanges', {
        params: { path: { id: organizationId } },
        body,
      })
      return { timeline: dataOf({ data, error }), noted: notedIn(response) }
    },
    onSuccess: ({ timeline }, { organizationId }) => {
      void queryClient.invalidateQueries({ queryKey: timelineKeys.ofContacts, refetchType: 'none' })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.ofOrganizations, refetchType: 'none' })
      queryClient.setQueryData(organizationTimelineQuery(organizationId).queryKey, {
        pages: [timeline],
        pageParams: [undefined],
      })
      toast.success(t('exchanges.noted'))
    },
  })
}

/** A corrected or erased exchange may be in several timelines, of contacts and of organizations. */
function readTheTimelinesAgain(queryClient: QueryClient) {
  void queryClient.invalidateQueries({ queryKey: timelineKeys.ofContacts })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.ofOrganizations })
}

/** The corrected exchange goes straight into its detail; the timelines showing it read it again. */
function useShowTheCorrection() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return (exchange: ExchangeDetail) => {
    queryClient.setQueryData(exchangeQuery(exchange.id).queryKey, exchange)
    readTheTimelinesAgain(queryClient)
    toast.success(t('exchanges.detail.correctionSaved'))
  }
}

export function useCorrectExchange() {
  const showTheCorrection = useShowTheCorrection()

  return api.useMutation('patch', '/api/exchanges/{id}/facts', {
    meta: { silentValidation: true },
    onSuccess: showTheCorrection,
  })
}

export function useCorrectExchangeText() {
  const showTheCorrection = useShowTheCorrection()

  return api.useMutation('patch', '/api/exchanges/{id}/text', {
    meta: { silentValidation: true },
    onSuccess: showTheCorrection,
  })
}

export function useEraseExchange() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('delete', '/api/exchanges/{id}', {
    onSuccess: (_nothing, { params }) => {
      queryClient.removeQueries({ queryKey: exchangeQuery(params.path.id).queryKey })
      readTheTimelinesAgain(queryClient)
      toast.success(t('exchanges.detail.erased'))
    },
  })
}

/**
 * The files are sent one after the other, the exchange as the last answer shows it going straight into its detail;
 * a file refused stops the others.
 */
export function useAttachFiles() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return useMutation({
    mutationFn: async ({ exchangeId, files }: { exchangeId: string; files: File[] }) => {
      for (const file of files) {
        queryClient.setQueryData(exchangeQuery(exchangeId).queryKey, await attachFile(exchangeId, file))
      }
    },
    onSuccess: (_nothing, { files }) => {
      readTheTimelinesAgain(queryClient)
      toast.success(t('exchanges.attachments.attached', { count: files.length }))
    },
  })
}

/** Once the exchange is noted, its files are sent while the timeline shows it; a refusal is told by a toast. */
export function attachTheFiles(attach: ReturnType<typeof useAttachFiles>, exchangeId: string, files: File[]) {
  if (files.length > 0) {
    attach.mutate({ exchangeId, files })
  }
}

export function useRemoveAttachment() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('delete', '/api/exchanges/{id}/attachments/{attachmentId}', {
    onSuccess: (exchange: ExchangeDetail) => {
      queryClient.setQueryData(exchangeQuery(exchange.id).queryKey, exchange)
      readTheTimelinesAgain(queryClient)
      toast.success(t('exchanges.attachments.removed'))
    },
  })
}
