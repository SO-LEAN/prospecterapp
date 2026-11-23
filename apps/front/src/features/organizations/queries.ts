import { infiniteQueryOptions } from '@tanstack/react-query'
import { api, fetchClient } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { dataOf, nextSliceOf } from '@/shared/api/slices'

export type Organization = components['schemas']['OrganizationResource']
export type OrganizationSummary = components['schemas']['OrganizationSummaryResource']
export type StartProspectingInput = components['schemas']['StartProspectingInput']
export type ReviseLegalIdentityInput = components['schemas']['ReviseLegalIdentityInput']
export type CollectContactInput = components['schemas']['CollectContactInput']

/** The first slice of the organizations that the search finds, for a picker or a summary. */
export const organizationsQuery = (q: string) =>
  api.queryOptions('get', '/api/organizations', { params: { query: { q } } })

/** The organizations that the search finds, one slice at a time. "Load more" asks for the next slice. */
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
export type OrganizationContacts = components['schemas']['OrganizationContactsResource']

export const organizationContactsQuery = (id: string) =>
  api.queryOptions('get', '/api/organizations/{id}/contacts', { params: { path: { id } } })

export type CollectedContact = components['schemas']['ContactResource']

/** A contact just collected, as its organization's contact list shows it before the API attaches it. */
export const asOrganizationContact = ({ _links, ...contact }: CollectedContact): OrganizationContact => ({
  ...contact,
  _links: { contact: _links.self },
})

/** A contact's name, first name then last name. It is empty when they have none. */
export const fullNameOf = (contact: Pick<OrganizationContact, 'name'>) =>
  [contact.name.firstName, contact.name.lastName].filter(Boolean).join(' ')

/** The contacts that the picker offers: the persons my organization prospects, searched by text. */
export const contactCandidatesQuery = (q: string) =>
  api.queryOptions('get', '/api/contacts', { params: { query: { q } } })

export type Establishment = components['schemas']['EstablishmentResource']

/** The query runs once a member opens the directory. When the directory is out of reach, the query fails at once, without a retry. */
export const establishmentsQuery = (id: string) =>
  api.queryOptions(
    'get',
    '/api/organizations/{id}/establishments',
    { params: { path: { id } } },
    { retry: false },
  )

export const organizationQuery = (id: string) =>
  api.queryOptions('get', '/api/organizations/{id}', { params: { path: { id } } })
