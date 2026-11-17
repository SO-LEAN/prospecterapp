import { infiniteQueryOptions } from '@tanstack/react-query'
import { api, fetchClient } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { dataOf, nextSliceOf } from '@/shared/api/slices'

export type Contact = components['schemas']['ContactResource']
export type ContactInList = components['schemas']['ContactInListResource']

/** The contacts found by the search, a slice after the other: "load more" asks for the next one. */
export const contactSlicesQuery = (q: string) =>
  infiniteQueryOptions({
    queryKey: ['get', '/api/contacts', { params: { query: { q } } }, 'slices'],
    queryFn: async ({ pageParam, signal }) =>
      dataOf(await fetchClient.GET('/api/contacts', { params: { query: { q, after: pageParam } }, signal })),
    initialPageParam: undefined as string | undefined,
    getNextPageParam: (slice) => nextSliceOf(slice._links),
  })

export const contactQuery = (id: string) =>
  api.queryOptions('get', '/api/contacts/{id}', { params: { path: { id } } })

export const organizationsOfContactQuery = (id: string) =>
  api.queryOptions('get', '/api/contacts/{id}/organizations', { params: { path: { id } } })

export const fullNameOf = (contact: Pick<Contact, 'name'>) =>
  [contact.name.firstName, contact.name.lastName].filter(Boolean).join(' ')
