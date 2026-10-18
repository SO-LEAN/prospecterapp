import { infiniteQueryOptions } from '@tanstack/react-query'
import { fetchClient } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { dataOf, nextSliceOf } from '@/shared/api/slices'

export type Timeline = components['schemas']['TimelineResource']
export type Exchange = components['schemas']['ExchangeResource']
export type ExchangeInput = components['schemas']['ExchangeInput']
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
    queryKey: ['get', '/api/contacts/{id}/exchanges', { params: { path: { id: contactId } } }, 'slices'],
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
