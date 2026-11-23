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
 * Every intention returns the fresh organization. It goes straight into the detail cache, without a refetch. The
 * lists are marked stale and refetched when displayed. The dashboard is read again once the gesture's reactions
 * counted it.
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

/**
 * An organization's location sets its geographic tags and the tags its contacts inherit (ADR 0142). The reactions to
 * its creation or to its move record them.
 */
function readTheTagsOfWhereItIsAgain(queryClient: QueryClient) {
  readAgainAfterTheReactions(queryClient, queryKeys.organizationTags, queryKeys.contactTags)
}

export function useStartProspecting() {
  const { t } = useTranslation()
  const queryClient = useQueryClient()
  const store = useStoreOrganization(({ name }) => t('organizations.start.started', { name }))

  return api.useMutation('post', '/api/organizations', {
    ...formIntention,
    onSuccess: (organization) => {
      store(organization)
      readTheTagsOfWhereItIsAgain(queryClient)
    },
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
  const queryClient = useQueryClient()
  const store = useStoreChangedOrganization()

  return api.useMutation('patch', '/api/organizations/{id}/address', {
    ...formIntention,
    onSuccess: (organization) => {
      store(organization)
      readTheTagsOfWhereItIsAgain(queryClient)
    },
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

/**
 * Once the gesture's reaction has run, the other organization is no longer flagged either.
 */
export function useDismissPossibleDuplicate() {
  const queryClient = useQueryClient()
  const store = useStoreChangedOrganization()

  return api.useMutation('delete', '/api/organizations/{id}/possible-duplicates/{duplicateId}', {
    onSuccess: (organization) => {
      store(organization)
      readAgainAfterTheReactions(queryClient, queryKeys.organization, queryKeys.organizationList)
    },
  })
}

export function useRemoveLogo() {
  return api.useMutation('delete', '/api/organizations/{id}/logo', {
    onSuccess: useStoreChangedOrganization(),
  })
}

/**
 * The organization changes, and so do its contact list and the contacts. The gesture's reactions erase a contact left
 * alone. An attached contact inherits the organization's tags, and a removed one loses them.
 */
function useStoreOrganizationAndItsContacts() {
  const queryClient = useQueryClient()
  const store = useStoreChangedOrganization()

  return (organization: Organization | undefined) => {
    store(organization)
    readAgainAfterTheReactions(
      queryClient,
      queryKeys.organizationContacts,
      queryKeys.contactList,
      queryKeys.contactTags,
    )
    readTheExchangesAgain(queryClient)
  }
}

/**
 * A new contact names its organizations, and a reaction attaches it to them. On an instance, that reaction may run
 * later than the front waits for the reactions. So each organization shows the contact at once, from the answer,
 * until a reading shows it attached there.
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

/** The waits between two readings of the organization's contacts, until the reaction has attached the new one. */
const UNTIL_THE_CONTACT_IS_ATTACHED_MS = [1_000, 1_500, 2_500, 5_000, 10_000] as const

/**
 * Reads the organization's contacts outside their cache until they hold the new contact. A reading without it would
 * erase it from the card. A failed reading is tried again at the next wait. Then the cache takes the contacts, with
 * what the attachment offers (removing the contact, its exchanges and its to-dos). The organization, the contact and
 * the contact's organizations are then read again.
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
 * The organization is gone. The gesture's reactions erase the contacts it leaves alone, and the exchanges that only
 * it kept.
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
