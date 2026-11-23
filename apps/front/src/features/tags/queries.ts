import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

export type Tags = components['schemas']['TagsResource']
export type ContactTags = components['schemas']['ContactTagsResource']
export type SuggestedTags = components['schemas']['SuggestedTagsResource']
export type TagFamily = components['schemas']['TagFamilyResource']['family']

/** An organization's tags: the tags put by hand (ADR 0138), and the tags for where it is (ADR 0142). */
export const organizationTagsQuery = (organizationId: string) =>
  api.queryOptions('get', '/api/organizations/{id}/tags', { params: { path: { id: organizationId } } })

/**
 * A contact's tags: their own tags, the tags they inherit from their organizations, and the automatic tags
 * (ADR 0141).
 */
export const contactTagsQuery = (contactId: string) =>
  api.queryOptions('get', '/api/contacts/{id}/tags', { params: { path: { id: contactId } } })

/**
 * The tags proposed to my account: the predefined tags by family, then the tags its organizations and
 * contacts carry. Separately come the automatic tags for where its organizations are, which nobody puts by
 * hand (ADR 0142).
 */
export const suggestedTagsQuery = () => api.queryOptions('get', '/api/tags')

export const tagKeys = { suggested: ['get', '/api/tags'] } as const
