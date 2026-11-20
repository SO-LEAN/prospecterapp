import { infiniteQueryOptions, queryOptions } from '@tanstack/react-query'
import { api, fetchClient } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import type { components } from '@/shared/api/schema'
import { dataOf, nextSliceOf } from '@/shared/api/slices'
import { pauseBeforeReadingAgain } from './awaited-push'

export type Timeline = components['schemas']['TimelineResource']
export type Exchange = components['schemas']['ExchangeResource']
/** Where an AI read a message (ADR 0166): the screen notes by hand, without it. */
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
/** What happened, all of it: what noting and correcting an exchange send; a correction may declare it made conform. */
export type ExchangeFacts = Omit<Correction, 'madeConform'> & Partial<Pick<Correction, 'madeConform'>>
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
/** Only a call, a meeting or a video call is planned, or missed: a message is just sent. */
export const PLANNED_CHANNELS = ['call', 'meeting', 'videoCall'] as const satisfies readonly Channel[]

export type PlannedChannel = (typeof PLANNED_CHANNELS)[number]

export const isAgreedUpon = (channel: Channel | undefined): channel is PlannedChannel =>
  PLANNED_CHANNELS.some((planned) => planned === channel)
export const DIRECTIONS = ['inbound', 'outbound'] as const satisfies readonly Direction[]

/** The words saying who made the first move: a message is sent, a call is made, a meeting or a video call is someone's initiative. */
export type DirectionWords = 'message' | 'call' | 'encounter'

export const directionWordsOf = (channel: Channel | undefined): DirectionWords => {
  if (channel === 'call') return 'call'
  return isAgreedUpon(channel) ? 'encounter' : 'message'
}

/** The exchanges with a contact, the most recent first, a slice after the other (ADR 0049, 0074). */
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

/** The exchanges with an organization and with its contacts, each with who communicated (ADR 0076). */
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

/** Only the exchanges of the organization to make conform, their contact forgotten (ADR 0079). */
export const exchangesToMakeConformQuery = (organizationId: string) =>
  infiniteQueryOptions({
    queryKey: [...timelineKeys.toMakeConform, { params: { path: { id: organizationId } } }, 'slices'],
    queryFn: async ({ pageParam, signal }) =>
      dataOf(
        await fetchClient.GET('/api/organizations/{id}/exchanges-to-make-conform', {
          params: { path: { id: organizationId }, query: { after: pageParam } },
          signal,
        }),
      ),
    initialPageParam: undefined as string | undefined,
    getNextPageParam: (slice) => nextSliceOf(slice._links),
  })

/** The exchanges to come with a contact, the soonest first, a slice after the other: the first is the next one. */
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

/** The exchanges to come with an organization and with its contacts, the soonest first, a slice after the other. */
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

/** The exchanges I planned overlapping a slot, before I plan another one in it. */
export const overlappingQuery = (startsAt: string, durationInMinutes: number) =>
  api.queryOptions('get', '/api/me/planned-exchanges', { params: { query: { startsAt, durationInMinutes } } })

/** An exchange noted on one card shows in the timelines of the other cards: those of contacts and of organizations. */
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

/** Where the push of an exchange into my Google calendar stands, changed by the reactions to its gestures. */
export const googleSyncKey = ['get', '/api/exchanges/{id}/google-sync'] as const

/** Where the push of an exchange stands, read again a few times while it awaits its push, as its link (ADR 0137). */
export const googleSyncQuery = (id: string) =>
  queryOptions({
    ...api.queryOptions('get', '/api/exchanges/{id}/google-sync', { params: { path: { id } } }),
    refetchInterval: (query) =>
      pauseBeforeReadingAgain(awaitsItsPush(query.state.data), query.state.dataUpdateCount),
  })

/** Pending for no reason of mine, its push is on its way; awaiting my reconnection, nothing comes until I act. */
const awaitsItsPush = (sync: GoogleSync | undefined) =>
  sync?.sync?.state === 'pending' && sync.sync.reason === null

/** An exchange, read again a few times while it awaits the link its calendar creates (ADR 0137). */
export const exchangeQuery = (id: string) =>
  queryOptions({
    ...api.queryOptions('get', '/api/exchanges/{id}', { params: { path: { id } } }),
    refetchInterval: (query) =>
      pauseBeforeReadingAgain(query.state.data?.linkAwaited ?? false, query.state.dataUpdateCount),
  })

/**
 * My connections as the banner of the app keeps them, the same query, read from its cache and never asked again by a
 * form: a Meet link is offered while Google is connected.
 */
export const myConnectionsQuery = () => ({
  ...api.queryOptions('get', '/api/me/connections'),
  enabled: false,
})

export const isGoogleConnected = (connections: Connections | undefined) =>
  connections?.connections.some(({ provider, state }) => provider === 'google' && state === 'connected') ??
  false

/** Where to meet a contact: the address of their organization, proposed while planning a meeting. */
export const meetingPlaceQuery = (contactId: string) =>
  api.queryOptions('get', '/api/contacts/{id}/meeting-place', { params: { path: { id: contactId } } })
