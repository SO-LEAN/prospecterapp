import { infiniteQueryOptions } from '@tanstack/react-query'
import { api, fetchClient } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { dataOf, nextSliceOf } from '@/shared/api/slices'

export type Campaign = components['schemas']['CampaignResource']
export type CampaignList = components['schemas']['CampaignListResource']
export type CampaignInput = components['schemas']['CampaignInput']
export type CampaignMessage = components['schemas']['CampaignMessageResource']
export type Channel = Campaign['channel']
export type TargetedContact = components['schemas']['ContactInListResource']

/** The campaigns of the team, read whole, each with its target counted (ADR 0144). */
export const campaignsQuery = () => api.queryOptions('get', '/api/campaigns')

export const campaignQuery = (campaignId: string) =>
  api.queryOptions('get', '/api/campaigns/{id}', { params: { path: { id: campaignId } } })

/** The message of the campaign as saved, rendered by the api for one contact: the front never renders it. */
export const campaignMessageQuery = (campaignId: string, contactId: string) =>
  api.queryOptions('get', '/api/campaigns/{id}/messages/{contactId}', {
    params: { path: { id: campaignId, contactId } },
  })

export const campaignKeys = {
  list: ['get', '/api/campaigns'],
  messages: ['get', '/api/campaigns/{id}/messages/{contactId}'],
} as const

/**
 * The contacts carrying every targeted tag, a slice after the other: the search by tags (ADR 0143), the same request
 * and the same key as the list of the contacts filtered by them, `tags` their names without their mark.
 */
export const campaignTargetQuery = (tags: string) =>
  infiniteQueryOptions({
    queryKey: ['get', '/api/tagged-contacts', { params: { query: { tags } } }, 'slices'],
    queryFn: async ({ pageParam, signal }) =>
      dataOf(
        await fetchClient.GET('/api/tagged-contacts', {
          params: { query: { tags, after: pageParam } },
          signal,
        }),
      ),
    initialPageParam: undefined as string | undefined,
    getNextPageParam: (slice) => nextSliceOf(slice._links),
  })
