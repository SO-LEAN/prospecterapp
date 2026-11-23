import { infiniteQueryOptions, queryOptions } from '@tanstack/react-query'
import { api, fetchClient } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import type { components } from '@/shared/api/schema'
import { dataOf, nextSliceOf } from '@/shared/api/slices'
import { pauseBeforeReadingAgain } from './awaited-push'

export type Timeline = components['schemas']['TimelineResource']
export type Exchange = components['schemas']['ExchangeResource']
/** Where an AI read a message (ADR 0166). The screen logs by hand and sends none of it. */
type ReferenceOfAnAi = 'messageId' | 'linkedInConversation'
export type ExchangeInput = Omit<components['schemas']['ExchangeInput'], ReferenceOfAnAi>
export const NOTED_BY_HAND: Pick<components['schemas']['ExchangeInput'], ReferenceOfAnAi> = {
  messageId: null,
  linkedInConversation: null,
}
export type PlannedExchangeInput = components['schemas']['PlannedExchangeInput']
export type OrganizationPlannedExchangeInput = components['schemas']['OrganizationPlannedExchangeInput']
export type PlannedExchanges = components['schemas']['PlannedExchangesResource']
export type OrganizationExchangeInput = Omit<
  components['schemas']['OrganizationExchangeInput'],
  'exchange'
> & {
  exchange: ExchangeInput
}
export type Attachment = components['schemas']['AttachmentResource']
export type OrganizationTimeline = components['schemas']['OrganizationTimelineResource']
export type OrganizationExchange = components['schemas']['OrganizationExchangeResource']
export type Interlocutor = OrganizationExchange['interlocutor']
export type ContactPresence = NonNullable<Interlocutor['presence']>
type Correction = components['schemas']['CorrectionInput']
/** All the facts of what happened: what logging and correcting an exchange send. A correction may also declare it redacted. */
export type ExchangeFacts = Omit<Correction, 'redacted'> & Partial<Pick<Correction, 'redacted'>>
export type ExchangeDetail = components['schemas']['ExchangeDetailResource']
export type GoogleSync = components['schemas']['ExchangeSyncResource']
export type Connections = components['schemas']['ConnectionsResource']
export type TextCorrection = components['schemas']['TextCorrectionInput']
export type Report = components['schemas']['ReportInput']
export type Moment = components['schemas']['MomentInput']
export type Canceller = NonNullable<components['schemas']['CancellationInput']['canceller']>
export type VenueCorrection = components['schemas']['VenueCorrectionInput']
export type Channel = Exchange['channel']
export type Direction = Exchange['direction']

export const CHANNELS = [
  'call',
  'sms',
  'email',
  'linkedIn',
  'malt',
  'meeting',
  'videoCall',
  'other',
] as const satisfies readonly Channel[]
/** Only a call, a meeting or a video call can be planned or missed. A message is just sent. */
export const PLANNED_CHANNELS = ['call', 'meeting', 'videoCall'] as const satisfies readonly Channel[]

export type PlannedChannel = (typeof PLANNED_CHANNELS)[number]

export const isAgreedUpon = (channel: Channel | undefined): channel is PlannedChannel =>
  PLANNED_CHANNELS.some((planned) => planned === channel)
export const DIRECTIONS = ['inbound', 'outbound'] as const satisfies readonly Direction[]

/** The words that say who made the first move: someone sends a message, makes a call, or takes the initiative of a meeting or a video call. */
export type DirectionWords = 'message' | 'call' | 'encounter'

export const directionWordsOf = (channel: Channel | undefined): DirectionWords => {
  if (channel === 'call') return 'call'
  return isAgreedUpon(channel) ? 'encounter' : 'message'
}

/** The exchanges with a contact, the most recent first, one slice at a time (ADR 0049, 0074). */
export const timelineQuery = (contactId: string) =>
  infiniteQueryOptions({
    queryKey: [...timelineKeys.ofContacts, { params: { path: { id: contactId } } }, 'slices'],
    queryFn: async ({ pageParam, signal }) =>
      dataOf(
        await fetchClient.GET('/api/contacts/{id}/exchanges', {
          params: { path: { id: contactId }, query: { after: pageParam } },
          signal,
        }),
      ),
    initialPageParam: undefined as string | undefined,
    getNextPageParam: (slice) => nextSliceOf(slice._links),
  })

/** The exchanges with an organization and with its contacts. Each one says who communicated (ADR 0076). */
export const organizationTimelineQuery = (organizationId: string) =>
  infiniteQueryOptions({
    queryKey: [
      'get',
      '/api/organizations/{id}/exchanges',
      { params: { path: { id: organizationId } } },
      'slices',
    ],
    queryFn: async ({ pageParam, signal }) =>
      dataOf(
        await fetchClient.GET('/api/organizations/{id}/exchanges', {
          params: { path: { id: organizationId }, query: { after: pageParam } },
          signal,
        }),
      ),
    initialPageParam: undefined as string | undefined,
    getNextPageParam: (slice) => nextSliceOf(slice._links),
  })

/** Only the organization's exchanges to redact, because their contact was forgotten (ADR 0079). */
export const exchangesToMakeConformQuery = (organizationId: string) =>
  infiniteQueryOptions({
    queryKey: [...timelineKeys.toMakeConform, { params: { path: { id: organizationId } } }, 'slices'],
    queryFn: async ({ pageParam, signal }) =>
      dataOf(
        await fetchClient.GET('/api/organizations/{id}/exchanges-to-redact', {
          params: { path: { id: organizationId }, query: { after: pageParam } },
          signal,
        }),
      ),
    initialPageParam: undefined as string | undefined,
    getNextPageParam: (slice) => nextSliceOf(slice._links),
  })

/** The upcoming exchanges with a contact, the soonest first, one slice at a time. The first one is the next exchange. */
export const upcomingQuery = (contactId: string) =>
  infiniteQueryOptions({
    queryKey: [...timelineKeys.upcomingOfContacts, { params: { path: { id: contactId } } }, 'slices'],
    queryFn: async ({ pageParam, signal }) =>
      dataOf(
        await fetchClient.GET('/api/contacts/{id}/upcoming-exchanges', {
          params: { path: { id: contactId }, query: { after: pageParam } },
          signal,
        }),
      ),
    initialPageParam: undefined as string | undefined,
    getNextPageParam: (slice) => nextSliceOf(slice._links),
  })

/** The upcoming exchanges with an organization and with its contacts, the soonest first, one slice at a time. */
export const organizationUpcomingQuery = (organizationId: string) =>
  infiniteQueryOptions({
    queryKey: [
      ...timelineKeys.upcomingOfOrganizations,
      { params: { path: { id: organizationId } } },
      'slices',
    ],
    queryFn: async ({ pageParam, signal }) =>
      dataOf(
        await fetchClient.GET('/api/organizations/{id}/upcoming-exchanges', {
          params: { path: { id: organizationId }, query: { after: pageParam } },
          signal,
        }),
      ),
    initialPageParam: undefined as string | undefined,
    getNextPageParam: (slice) => nextSliceOf(slice._links),
  })

/** The exchanges I planned that overlap a slot, read before I plan another one in it. */
export const overlappingQuery = (startsAt: string, durationInMinutes: number) =>
  api.queryOptions('get', '/api/me/planned-exchanges', { params: { query: { startsAt, durationInMinutes } } })

/** An exchange logged on one card shows in the other cards' timelines: the contacts' and the organizations'. */
export const timelineKeys = {
  ofContacts: ['get', '/api/contacts/{id}/exchanges'],
  ofOrganizations: queryKeys.organizationTimelines,
  toMakeConform: queryKeys.exchangesToMakeConform,
  upcomingOfContacts: ['get', '/api/contacts/{id}/upcoming-exchanges'],
  upcomingOfOrganizations: queryKeys.organizationUpcomingExchanges,
  myPlanned: ['get', '/api/me/planned-exchanges'],
  myCalendar: queryKeys.myCalendar,
  myDay: queryKeys.myDay,
  toProcess: queryKeys.exchangesToProcess,
} as const

/** Where an exchange's push into my Google calendar stands. The reactions to the exchange's gestures change it. */
export const googleSyncKey = ['get', '/api/exchanges/{id}/google-sync'] as const

/** Where an exchange's push stands. Like its link, it is read again a few times while the exchange awaits its push (ADR 0137). */
export const googleSyncQuery = (id: string) =>
  queryOptions({
    ...api.queryOptions('get', '/api/exchanges/{id}/google-sync', { params: { path: { id } } }),
    refetchInterval: (query) =>
      pauseBeforeReadingAgain(awaitsItsPush(query.state.data), query.state.dataUpdateCount),
  })

/** A push pending for no reason on my side is on its way. A push awaiting my reconnection brings nothing until I act. */
const awaitsItsPush = (sync: GoogleSync | undefined) =>
  sync?.sync?.state === 'pending' && sync.sync.reason === null

/** An exchange, read again a few times while it awaits the link that its calendar creates (ADR 0137). */
export const exchangeQuery = (id: string) =>
  queryOptions({
    ...api.queryOptions('get', '/api/exchanges/{id}', { params: { path: { id } } }),
    refetchInterval: (query) =>
      pauseBeforeReadingAgain(query.state.data?.linkAwaited ?? false, query.state.dataUpdateCount),
  })

/**
 * My connections as the app's banner keeps them. It is the same query, read from its cache, and a form never asks
 * for it again. A form offers a Meet link while Google is connected.
 */
export const myConnectionsQuery = () => ({
  ...api.queryOptions('get', '/api/me/connections'),
  enabled: false,
})

export const isGoogleConnected = (connections: Connections | undefined) =>
  connections?.connections.some(({ provider, state }) => provider === 'google' && state === 'connected') ??
  false

/** Where to meet a contact: their organization's address, proposed while the member plans a meeting. */
export const meetingPlaceQuery = (contactId: string) =>
  api.queryOptions('get', '/api/contacts/{id}/meeting-place', { params: { path: { id: contactId } } })
