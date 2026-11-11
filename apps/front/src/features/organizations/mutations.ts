import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api, fetchClient } from '@/shared/api/client'
import { readTheExchangesAgain } from '@/shared/api/exchanges'
import { queryKeys } from '@/shared/api/keys'
import { readAgainAfterTheReactions } from '@/shared/api/reactions'
import { fileBody } from '@/shared/api/upload'
import { type Organization, organizationQuery } from './queries'

/**
 * Every intention returns the fresh organization: it goes straight into the detail cache (no refetch),
 * while lists are marked stale and refetched when displayed, and the dashboard read again once the reactions to
 * the gesture counted it.
 */
function useStoreOrganization(confirmation: (organization: Organization) => string) {
  const queryClient = useQueryClient()

  return (organization: Organization | undefined) => {
    if (!organization) {
      return
    }
    queryClient.setQueryData(organizationQuery(organization.id).queryKey, organization)
    void queryClient.invalidateQueries({ queryKey: queryKeys.organizationList })
    readAgainAfterTheReactions(queryClient, queryKeys.dashboard)
    toast.success(confirmation(organization))
  }
}

function useStoreChangedOrganization() {
  const { t } = useTranslation()

  return useStoreOrganization(() => t('common.saved'))
}

const formIntention = { meta: { silentValidation: true } }

export function useStartProspecting() {
  const { t } = useTranslation()

  return api.useMutation('post', '/api/organizations', {
    ...formIntention,
    onSuccess: useStoreOrganization(({ name }) => t('organizations.start.started', { name })),
  })
}

export function useReviseLegalIdentity() {
  return api.useMutation('patch', '/api/organizations/{id}/legal-identity', {
    ...formIntention,
    onSuccess: useStoreChangedOrganization(),
  })
}

export function useChangeContactDetails() {
  return api.useMutation('patch', '/api/organizations/{id}/contact-details', {
    ...formIntention,
    onSuccess: useStoreChangedOrganization(),
  })
}

export function useRelocate() {
  return api.useMutation('patch', '/api/organizations/{id}/address', {
    ...formIntention,
    onSuccess: useStoreChangedOrganization(),
  })
}

export function useWriteObservations() {
  return api.useMutation('patch', '/api/organizations/{id}/observations', {
    ...formIntention,
    onSuccess: useStoreChangedOrganization(),
  })
}

export function useAttachToHolding() {
  return api.useMutation('patch', '/api/organizations/{id}/holding', {
    onSuccess: useStoreChangedOrganization(),
  })
}

export function useDetachFromHolding() {
  return api.useMutation('delete', '/api/organizations/{id}/holding', {
    onSuccess: useStoreChangedOrganization(),
  })
}

export function useRemoveLogo() {
  return api.useMutation('delete', '/api/organizations/{id}/logo', {
    onSuccess: useStoreChangedOrganization(),
  })
}

/**
 * The organization changes, and so do the list of its contacts and the contacts: one left alone is erased by the
 * reactions to the gesture.
 */
function useStoreOrganizationAndItsContacts() {
  const queryClient = useQueryClient()
  const store = useStoreChangedOrganization()

  return (organization: Organization | undefined) => {
    store(organization)
    readAgainAfterTheReactions(queryClient, queryKeys.organizationContacts, queryKeys.contactList)
    readTheExchangesAgain(queryClient)
  }
}

/** A new contact names the organization, which a reaction attaches it to: the organization and its contacts are read again. */
export function useCollectContact() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/contacts', {
    ...formIntention,
    onSuccess: () => {
      readAgainAfterTheReactions(
        queryClient,
        queryKeys.organization,
        queryKeys.organizationContacts,
        queryKeys.contactList,
        queryKeys.dashboard,
      )
      toast.success(t('common.saved'))
    },
  })
}

/** The organization is gone; its contacts left alone are erased with it, by the reactions to the gesture. */
export function useStopProspecting() {
  const queryClient = useQueryClient()

  return api.useMutation('delete', '/api/organizations/{id}', {
    onSuccess: (_nothing, { params }) => {
      queryClient.removeQueries({ queryKey: organizationQuery(params.path.id).queryKey })
      readAgainAfterTheReactions(
        queryClient,
        queryKeys.organizationList,
        queryKeys.dashboard,
        queryKeys.contactList,
      )
    },
  })
}

export function useAddContact() {
  return api.useMutation('post', '/api/organizations/{id}/contacts', {
    onSuccess: useStoreOrganizationAndItsContacts(),
  })
}

export function useRemoveContact() {
  return api.useMutation('delete', '/api/organizations/{id}/contacts/{personId}', {
    onSuccess: useStoreOrganizationAndItsContacts(),
  })
}

export function useChangeLogo(id: string) {
  const store = useStoreChangedOrganization()

  return useMutation({
    mutationFn: async (file: File) => {
      const { data, error } = await fetchClient.POST('/api/organizations/{id}/logo', {
        params: { path: { id } },
        ...fileBody(file),
      })
      if (error || !data) {
        throw error
      }
      return data
    },
    onSuccess: store,
  })
}
