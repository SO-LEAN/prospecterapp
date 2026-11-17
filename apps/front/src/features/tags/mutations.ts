import { useQueryClient } from '@tanstack/react-query'
import { api } from '@/shared/api/client'
import { contactTagsQuery, organizationTagsQuery, tagKeys } from './queries'

/**
 * A tag put or taken off changes the tags proposed to the account: a tag of its own appears, or one nothing carries
 * any more leaves them. The tags answered go straight in the cache of what is tagged.
 */
export function useTagOrganization() {
  const queryClient = useQueryClient()

  return api.useMutation('post', '/api/organizations/{id}/tags', {
    onSuccess: (tags, { params }) => {
      queryClient.setQueryData(organizationTagsQuery(params.path.id).queryKey, tags)
      return queryClient.invalidateQueries({ queryKey: tagKeys.suggested })
    },
  })
}

export function useTakeOffOrganizationTag() {
  const queryClient = useQueryClient()

  return api.useMutation('delete', '/api/organizations/{id}/tags/{tag}', {
    onSuccess: (tags, { params }) => {
      queryClient.setQueryData(organizationTagsQuery(params.path.id).queryKey, tags)
      return queryClient.invalidateQueries({ queryKey: tagKeys.suggested })
    },
  })
}

export function useTagContact() {
  const queryClient = useQueryClient()

  return api.useMutation('post', '/api/contacts/{id}/tags', {
    onSuccess: (tags, { params }) => {
      queryClient.setQueryData(contactTagsQuery(params.path.id).queryKey, tags)
      return queryClient.invalidateQueries({ queryKey: tagKeys.suggested })
    },
  })
}

export function useTakeOffContactTag() {
  const queryClient = useQueryClient()

  return api.useMutation('delete', '/api/contacts/{id}/tags/{tag}', {
    onSuccess: (tags, { params }) => {
      queryClient.setQueryData(contactTagsQuery(params.path.id).queryKey, tags)
      return queryClient.invalidateQueries({ queryKey: tagKeys.suggested })
    },
  })
}

/** The tags chosen in a form before the organization existed, put once it is created. */
export function useTagTheNewOrganization() {
  const tag = useTagOrganization()

  return (organizationId: string, tags: string[]) =>
    tags.length === 0
      ? Promise.resolve()
      : tag.mutateAsync({ params: { path: { id: organizationId } }, body: { tags } })
}
