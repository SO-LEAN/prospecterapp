import { infiniteQueryOptions } from '@tanstack/react-query'
import { api, fetchClient } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { dataOf, nextSliceOf } from '@/shared/api/slices'

export type Organization = components['schemas']['OrganizationResource']
export type OrganizationSummary = components['schemas']['OrganizationSummaryResource']
export type StartProspectingInput = components['schemas']['StartProspectingInput']
export type CollectContactInput = components['schemas']['CollectContactInput']

/** The first slice of the organizations found by the search, for a picker or a summary. */
export const organizationsQuery = (q: string) =>
  api.queryOptions('get', '/api/organizations', { params: { query: { q } } })

/** The organizations found by the search, a slice after the other: "load more" asks for the next one. */
export const organizationSlicesQuery = (q: string) =>
  infiniteQueryOptions({
    queryKey: ['get', '/api/organizations', { params: { query: { q } } }, 'slices'],
    queryFn: async ({ pageParam, signal }) =>
      dataOf(
        await fetchClient.GET('/api/organizations', { params: { query: { q, after: pageParam } }, signal }),
      ),
    initialPageParam: undefined as string | undefined,
    getNextPageParam: (slice) => nextSliceOf(slice._links),
  })

export type OrganizationContact = components['schemas']['OrganizationContactResource']

export const organizationContactsQuery = (id: string) =>
  api.queryOptions('get', '/api/organizations/{id}/contacts', { params: { path: { id } } })

/** The contacts the picker offers: the persons my organization prospects, searched by text. */
export const contactCandidatesQuery = (q: string) =>
  api.queryOptions('get', '/api/contacts', { params: { query: { q } } })

export const organizationQuery = (id: string) =>
  api.queryOptions('get', '/api/organizations/{id}', { params: { path: { id } } })
