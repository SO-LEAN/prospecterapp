import { type QueryClient, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api, fetchClient } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import { readAgainAfterTheReactions } from '@/shared/api/reactions'
import { dataOf } from '@/shared/api/slices'
import { attachFile } from './attachments'
import {
  type ExchangeDetail,
  type ExchangeInput,
  exchangeQuery,
  type OrganizationExchangeInput,
  organizationTimelineQuery,
  organizationUpcomingQuery,
  timelineKeys,
  timelineQuery,
  upcomingQuery,
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
      readTheFiguresAgain(queryClient)
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
      readTheFiguresAgain(queryClient)
      toast.success(t('exchanges.noted'))
    },
  })
}

/**
 * The API answers the exchanges to come with the contact, the new one among them; those of its organizations and my
 * agenda will be read again.
 */
export function usePlanExchange() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/contacts/{id}/upcoming-exchanges', {
    meta: { silentValidation: true },
    onSuccess: (upcoming, { params }) => {
      queryClient.setQueryData(upcomingQuery(params.path.id).queryKey, {
        pages: [upcoming],
        pageParams: [undefined],
      })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.upcomingOfOrganizations })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.myPlanned })
      toast.success(t('exchanges.planned'))
    },
  })
}

/**
 * Planned from the card of an organization: its exchanges to come as the API answers them; those of its contacts and
 * of their other organizations, and my agenda, will be read again.
 */
export function usePlanOrganizationExchange() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/organizations/{id}/upcoming-exchanges', {
    meta: { silentValidation: true },
    onSuccess: (upcoming, { params }) => {
      void queryClient.invalidateQueries({ queryKey: timelineKeys.upcomingOfContacts })
      void queryClient.invalidateQueries({
        queryKey: timelineKeys.upcomingOfOrganizations,
        refetchType: 'none',
      })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.myPlanned })
      queryClient.setQueryData(organizationUpcomingQuery(params.path.id).queryKey, {
        pages: [upcoming],
        pageParams: [undefined],
      })
      toast.success(t('exchanges.planned'))
    },
  })
}

/** The dashboard counts the exchanges, those of the month by their moment, once the reactions to the gesture ran. */
function readTheFiguresAgain(queryClient: QueryClient) {
  readAgainAfterTheReactions(queryClient, queryKeys.dashboard)
}

/** A corrected or erased exchange may be in several timelines, of contacts and of organizations, past or to come. */
function readTheTimelinesAgain(queryClient: QueryClient) {
  void queryClient.invalidateQueries({ queryKey: timelineKeys.ofContacts })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.ofOrganizations })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.toMakeConform })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.upcomingOfContacts })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.upcomingOfOrganizations })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.myPlanned })
}

/** The corrected exchange goes straight into its detail; the timelines showing it read it again. */
function useShowTheCorrection() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return (exchange: ExchangeDetail) => {
    queryClient.setQueryData(exchangeQuery(exchange.id).queryKey, exchange)
    readTheTimelinesAgain(queryClient)
    readTheFiguresAgain(queryClient)
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
      readTheFiguresAgain(queryClient)
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
