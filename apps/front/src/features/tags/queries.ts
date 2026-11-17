import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

export type Tags = components['schemas']['TagsResource']
export type SuggestedTags = components['schemas']['SuggestedTagsResource']
export type TagFamily = components['schemas']['TagFamilyResource']['family']

/** The tags put by hand on an organization (ADR 0138). */
export const organizationTagsQuery = (organizationId: string) =>
  api.queryOptions('get', '/api/organizations/{id}/tags', { params: { path: { id: organizationId } } })

/** The tags put by hand on a contact. */
export const contactTagsQuery = (contactId: string) =>
  api.queryOptions('get', '/api/contacts/{id}/tags', { params: { path: { id: contactId } } })

/** The tags proposed to my account: the predefined ones by family, then those its organizations and contacts carry. */
export const suggestedTagsQuery = () => api.queryOptions('get', '/api/tags')

export const tagKeys = { suggested: ['get', '/api/tags'] } as const
