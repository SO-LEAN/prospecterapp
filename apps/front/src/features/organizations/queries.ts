import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

export type Organization = components['schemas']['OrganizationResource']
export type OrganizationSummary = components['schemas']['OrganizationSummaryResource']
export type StartProspectingInput = components['schemas']['StartProspectingInput']

export type OrganizationSearch = { page: number; q: string }

export const organizationsQuery = ({ page, q }: OrganizationSearch) =>
  api.queryOptions('get', '/api/organizations', { params: { query: { page, q } } })

export type OrganizationContact = components['schemas']['OrganizationContactResource']

export const organizationContactsQuery = (id: string) =>
  api.queryOptions('get', '/api/organizations/{id}/contacts', { params: { path: { id } } })

/** The contacts the picker offers: the persons my organization prospects, searched by text. */
export const contactCandidatesQuery = (q: string) =>
  api.queryOptions('get', '/api/contacts', { params: { query: { page: 1, q } } })

export const organizationQuery = (id: string) =>
  api.queryOptions('get', '/api/organizations/{id}', { params: { path: { id } } })
