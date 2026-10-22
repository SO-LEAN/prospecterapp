import { infiniteQueryOptions } from '@tanstack/react-query'
import { api, fetchClient } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { dataOf, nextSliceOf } from '@/shared/api/slices'

export type Timeline = components['schemas']['TimelineResource']
export type Exchange = components['schemas']['ExchangeResource']
export type ExchangeInput = components['schemas']['ExchangeInput']
export type OrganizationExchangeInput = components['schemas']['OrganizationExchangeInput']
export type Attachment = components['schemas']['AttachmentResource']
export type OrganizationTimeline = components['schemas']['OrganizationTimelineResource']
export type OrganizationExchange = components['schemas']['OrganizationExchangeResource']
export type Interlocutor = OrganizationExchange['interlocutor']
/** What happened, all of it: what noting and correcting an exchange send. */
export type ExchangeFacts = components['schemas']['CorrectionInput']
export type ExchangeDetail = components['schemas']['ExchangeDetailResource']
export type TextCorrection = components['schemas']['TextCorrectionInput']
export type Author = components['schemas']['AuthorResource']
export type Channel = Exchange['channel']
export type Direction = Exchange['direction']

export const CHANNELS = [
  'call',
  'sms',
  'email',
  'linkedIn',
  'meeting',
  'other',
] as const satisfies readonly Channel[]
export const DIRECTIONS = ['inbound', 'outbound'] as const satisfies readonly Direction[]

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

/** An exchange noted on one card shows in the timelines of the other cards: those of contacts and of organizations. */
export const timelineKeys = {
  ofContacts: ['get', '/api/contacts/{id}/exchanges'],
  ofOrganizations: ['get', '/api/organizations/{id}/exchanges'],
} as const

export const exchangeQuery = (id: string) =>
  api.queryOptions('get', '/api/exchanges/{id}', { params: { path: { id } } })
