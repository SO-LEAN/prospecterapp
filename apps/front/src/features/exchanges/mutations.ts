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
  type GoogleSync,
  googleSyncKey,
  googleSyncQuery,
  NOTED_BY_HAND,
  type OrganizationExchangeInput,
  organizationTimelineQuery,
  organizationUpcomingQuery,
  timelineKeys,
  timelineQuery,
  upcomingQuery,
} from './queries'

/** The new exchange, whose address the API returns in `Location`. The files are attached to it. */
const notedIn = (response: Response) => response.headers.get('Location')?.split('/').pop() ?? ''

/**
 * The API returns the timeline's first slice with the new exchange. The timeline starts again from that slice, and
 * the timelines of the contact's organizations will be read again.
 */
export function useNoteExchange() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return useMutation({
    meta: { silentValidation: true },
    mutationFn: async ({ contactId, body }: { contactId: string; body: ExchangeInput }) => {
      const { data, error, response } = await fetchClient.POST('/api/contacts/{id}/exchanges', {
        params: { path: { id: contactId } },
        body: { ...body, ...NOTED_BY_HAND },
      })
      return { timeline: dataOf({ data, error }), noted: notedIn(response) }
    },
    onSuccess: ({ timeline }, { contactId }) => {
      queryClient.setQueryData(timelineQuery(contactId).queryKey, {
        pages: [timeline],
        pageParams: [undefined],
      })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.ofOrganizations })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.myCalendar })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.myDay })
      readTheFiguresAgain(queryClient)
      toast.success(t('exchanges.noted'))
    },
  })
}

/**
 * Logs an exchange from an organization's card. Its timeline starts again from the first slice that the API returns.
 * The other timelines, those of its contacts and of their other organizations, will be read again when shown.
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
        body: { ...body, exchange: { ...body.exchange, ...NOTED_BY_HAND } },
      })
      return { timeline: dataOf({ data, error }), noted: notedIn(response) }
    },
    onSuccess: ({ timeline }, { organizationId }) => {
      void queryClient.invalidateQueries({ queryKey: timelineKeys.ofContacts, refetchType: 'none' })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.ofOrganizations, refetchType: 'none' })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.myCalendar })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.myDay })
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
 * The API returns the upcoming exchanges with the contact, the new one among them. The upcoming exchanges of the
 * contact's organizations and my agenda will be read again.
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
      void queryClient.invalidateQueries({ queryKey: timelineKeys.myCalendar })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.myDay })
      toast.success(t('exchanges.planned'))
    },
  })
}

/**
 * Plans an exchange from an organization's card. The API returns the organization's upcoming exchanges. Those of its
 * contacts and of their other organizations, and my agenda, will be read again.
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
      void queryClient.invalidateQueries({ queryKey: timelineKeys.myCalendar })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.myDay })
      queryClient.setQueryData(organizationUpcomingQuery(params.path.id).queryKey, {
        pages: [upcoming],
        pageParams: [undefined],
      })
      toast.success(t('exchanges.planned'))
    },
  })
}

/** Once the gesture's reactions have run, the dashboard counts the exchanges again, the month's ones by their moment. */
function readTheFiguresAgain(queryClient: QueryClient) {
  readAgainAfterTheReactions(queryClient, queryKeys.dashboard)
}

/** Once the reactions have run, the recipient that the erased exchange processed is to handle again (ADR 0152). */
function readTheCampaignsAgain(queryClient: QueryClient) {
  readAgainAfterTheReactions(
    queryClient,
    queryKeys.campaign,
    queryKeys.campaignRecipients,
    queryKeys.campaignList,
  )
}

/** A corrected or erased exchange may be in several timelines: contacts' and organizations', past or upcoming. */
export function readTheTimelinesAgain(queryClient: QueryClient) {
  void queryClient.invalidateQueries({ queryKey: timelineKeys.ofContacts })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.ofOrganizations })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.toMakeConform })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.upcomingOfContacts })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.upcomingOfOrganizations })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.myPlanned })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.myCalendar })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.myDay })
  void queryClient.invalidateQueries({ queryKey: timelineKeys.toProcess })
  readAgainAfterTheReactions(queryClient, googleSyncKey)
}

/** The corrected exchange goes straight into its detail. The timelines that show it read it again. */
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

export function useCorrectExchangeVenue() {
  const showTheCorrection = useShowTheCorrection()

  return api.useMutation('patch', '/api/exchanges/{id}/venue', {
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
      readTheCampaignsAgain(queryClient)
      toast.success(t('exchanges.detail.erased'))
    },
  })
}

/**
 * Sends the files one after the other. The exchange, as the last answer shows it, goes straight into its detail. A
 * refused file stops the others.
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

/** Once the exchange is logged, its files are sent while the timeline shows it. A toast shows a refusal. */
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

/** Whose e-mails the catch-up imports: a contact's, or an organization's own. */
export type CatchUpCorrespondent = { contactId: string } | { organizationId: string }

/**
 * The Gmail catch-up answers at once, and the import logs the e-mails a moment later. Once the reactions have run,
 * the timelines that show the e-mails are read again, including those of a contact's organizations.
 */
export function useCatchUpGmail() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return useMutation({
    mutationFn: async (correspondent: CatchUpCorrespondent) => {
      const { error } =
        'contactId' in correspondent
          ? await fetchClient.POST('/api/contacts/{id}/gmail-reimports', {
              params: { path: { id: correspondent.contactId } },
            })
          : await fetchClient.POST('/api/organizations/{id}/gmail-reimports', {
              params: { path: { id: correspondent.organizationId } },
            })
      if (error) {
        throw error
      }
    },
    onSuccess: (_nothing, correspondent) => {
      readAgainAfterTheReactions(
        queryClient,
        ...('contactId' in correspondent
          ? [timelineQuery(correspondent.contactId).queryKey, timelineKeys.ofOrganizations]
          : [organizationTimelineQuery(correspondent.organizationId).queryKey]),
      )
      readTheFiguresAgain(queryClient)
      toast.success(t('exchanges.catchUpGmail.started'))
    },
  })
}

/** Its holder pushes the exchange into my Google calendar again. It then shows where its push currently stands (ADR 0133). */
export function useRetryGooglePush() {
  const queryClient = useQueryClient()

  return api.useMutation('post', '/api/exchanges/{id}/google-sync/retries', {
    onSuccess: (sync: GoogleSync, { params }) =>
      queryClient.setQueryData(googleSyncQuery(params.path.id).queryKey, sync),
  })
}

/** A planned exchange marked done goes straight into its detail. Its timelines and the figures are read again. */
export function useMarkExchangeDone() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/exchanges/{id}/done', {
    meta: { silentValidation: true },
    onSuccess: (exchange: ExchangeDetail) => {
      queryClient.setQueryData(exchangeQuery(exchange.id).queryKey, exchange)
      readTheTimelinesAgain(queryClient)
      readTheFiguresAgain(queryClient)
      toast.success(t('exchanges.handling.markedDone'))
    },
  })
}

export function useMoveExchange() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('patch', '/api/exchanges/{id}/moment', {
    meta: { silentValidation: true },
    onSuccess: (exchange: ExchangeDetail) => {
      queryClient.setQueryData(exchangeQuery(exchange.id).queryKey, exchange)
      readTheTimelinesAgain(queryClient)
      toast.success(t('exchanges.handling.moved'))
    },
  })
}

/**
 * An exchange marked missed or cancelled goes into its detail, and its timelines are read again. The gesture runs
 * once the toast that can take it back is gone, and its sheet may be closed by then. So what follows the gesture is
 * in the mutation's options, which run even once its component is gone.
 */
function useShowTheHandling() {
  const queryClient = useQueryClient()

  return (exchange: ExchangeDetail) => {
    queryClient.setQueryData(exchangeQuery(exchange.id).queryKey, exchange)
    readTheTimelinesAgain(queryClient)
    readTheFiguresAgain(queryClient)
  }
}

export function useMarkExchangeMissed() {
  return api.useMutation('post', '/api/exchanges/{id}/missed', { onSuccess: useShowTheHandling() })
}

export function useCancelExchange() {
  return api.useMutation('post', '/api/exchanges/{id}/cancellation', { onSuccess: useShowTheHandling() })
}

/** How long a toast lets the member take back the gesture it announces. */
export const UNDO_DELAY_MS = 5000

/**
 * Carries out a gesture after a few seconds, unless its toast's « Annuler » takes it back. `takenBack` then says
 * so.
 */
export function useUndoable() {
  const { t } = useTranslation()

  return (told: string, gesture: () => void, takenBack?: () => void) => {
    const pending = setTimeout(gesture, UNDO_DELAY_MS)
    toast(told, {
      duration: UNDO_DELAY_MS,
      action: {
        label: t('common.undo'),
        onClick: () => {
          clearTimeout(pending)
          takenBack?.()
        },
      },
    })
  }
}
