import { type QueryClient, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api, fetchClient } from '@/shared/api/client'
import { readTheExchangesAgain } from '@/shared/api/exchanges'
import { queryKeys } from '@/shared/api/keys'
import { readAgainAfterTheReactions } from '@/shared/api/reactions'
import { fileBody } from '@/shared/api/upload'
import {
  asOrganizationContact,
  type CollectedContact,
  type Organization,
  type OrganizationContacts,
  organizationContactsQuery,
  organizationQuery,
} from './queries'

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

/**
 * A new contact names its organizations, which a reaction attaches it to, maybe later than the reactions are awaited
 * on an instance: it is shown in each of them at once, from the answer, until it is read there attached.
 */
export function useCollectContact() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/contacts', {
    ...formIntention,
    onSuccess: (contact, { body }) => {
      for (const organizationId of body.organizationIds ?? []) {
        showInTheOrganization(queryClient, organizationId, contact)
        void readAgainOnceAttached(queryClient, organizationId, contact)
      }
      readAgainAfterTheReactions(queryClient, queryKeys.contactList, queryKeys.dashboard)
      toast.success(t('common.saved'))
    },
  })
}

function showInTheOrganization(queryClient: QueryClient, organizationId: string, contact: CollectedContact) {
  queryClient.setQueryData<Organization>(
    organizationQuery(organizationId).queryKey,
    (organization) =>
      organization && { ...organization, contactIds: [...organization.contactIds, contact.id] },
  )
  queryClient.setQueryData<OrganizationContacts>(
    organizationContactsQuery(organizationId).queryKey,
    (contacts) => contacts && { ...contacts, items: [...contacts.items, asOrganizationContact(contact)] },
  )
}

/** The waits between two readings of the contacts of the organization, until the reaction attached the new one. */
const UNTIL_THE_CONTACT_IS_ATTACHED_MS = [1_000, 1_500, 2_500, 5_000, 10_000] as const

/**
 * The contacts of the organization, read apart from their cache until they hold the new contact: a reading without
 * it would erase it from the card, a reading failing is tried again at the next wait. Then the cache takes them, with
 * what the attachment offers (removing the contact, its exchanges and to-dos), and the organization, the contact and
 * its organizations are read again.
 */
async function readAgainOnceAttached(
  queryClient: QueryClient,
  organizationId: string,
  contact: CollectedContact,
) {
  for (const wait of UNTIL_THE_CONTACT_IS_ATTACHED_MS) {
    await new Promise((resolve) => setTimeout(resolve, wait))
    const { data } = await fetchClient
      .GET('/api/organizations/{id}/contacts', { params: { path: { id: organizationId } } })
      .catch(() => ({ data: undefined }))
    if (data?.items.some(({ id }) => id === contact.id)) {
      queryClient.setQueryData(organizationContactsQuery(organizationId).queryKey, data)
      void queryClient.invalidateQueries({ queryKey: organizationQuery(organizationId).queryKey })
      readTheContactAgain(queryClient, contact.id)
      return
    }
  }
}

function readTheContactAgain(queryClient: QueryClient, id: string) {
  for (const queryKey of [queryKeys.contact, queryKeys.organizationsOfContact]) {
    void queryClient.invalidateQueries({ queryKey: [...queryKey, { params: { path: { id } } }] })
  }
}

/**
 * The organization is gone; its contacts left alone are erased with it, and the exchanges it kept alone, by the
 * reactions to the gesture.
 */
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
        queryKeys.myCalendar,
        queryKeys.myDay,
        queryKeys.exchangesToProcess,
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
