import { type QueryKey, useQueryClient } from '@tanstack/react-query'
import { api } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import { contactTagsQuery, organizationTagsQuery, tagKeys } from './queries'

/**
 * A tag put or taken off changes the tags proposed to the account: a tag of its own appears, or one nothing carries
 * any more leaves them, and the tags the contacts show in their list. The tags answered go straight in the cache of
 * what is tagged; those of an organization change at once the tags its contacts inherit (ADR 0141).
 */
function useReadTheTagsAgain() {
  const queryClient = useQueryClient()

  return (...others: readonly QueryKey[]) =>
    Promise.all(
      [tagKeys.suggested, queryKeys.contactList, ...others].map((queryKey) =>
        queryClient.invalidateQueries({ queryKey }),
      ),
    )
}

export function useTagOrganization() {
  const queryClient = useQueryClient()
  const readTheTagsAgain = useReadTheTagsAgain()

  return api.useMutation('post', '/api/organizations/{id}/tags', {
    onSuccess: (tags, { params }) => {
      queryClient.setQueryData(organizationTagsQuery(params.path.id).queryKey, tags)
      return readTheTagsAgain(queryKeys.contactTags)
    },
  })
}

export function useTakeOffOrganizationTag() {
  const queryClient = useQueryClient()
  const readTheTagsAgain = useReadTheTagsAgain()

  return api.useMutation('delete', '/api/organizations/{id}/tags/{tag}', {
    onSuccess: (tags, { params }) => {
      queryClient.setQueryData(organizationTagsQuery(params.path.id).queryKey, tags)
      return readTheTagsAgain(queryKeys.contactTags)
    },
  })
}

export function useTagContact() {
  const queryClient = useQueryClient()
  const readTheTagsAgain = useReadTheTagsAgain()

  return api.useMutation('post', '/api/contacts/{id}/tags', {
    onSuccess: (tags, { params }) => {
      queryClient.setQueryData(contactTagsQuery(params.path.id).queryKey, tags)
      return readTheTagsAgain()
    },
  })
}

export function useTakeOffContactTag() {
  const queryClient = useQueryClient()
  const readTheTagsAgain = useReadTheTagsAgain()

  return api.useMutation('delete', '/api/contacts/{id}/tags/{tag}', {
    onSuccess: (tags, { params }) => {
      queryClient.setQueryData(contactTagsQuery(params.path.id).queryKey, tags)
      return readTheTagsAgain()
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
