import { useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api } from '@/shared/api/client'
import { readTheExchangesAgain } from '@/shared/api/exchanges'
import { queryKeys } from '@/shared/api/keys'
import { type Contact, contactQuery } from './queries'

/** The contacts listed elsewhere (the list, the contacts of the organizations) show what changed. */
function useRefreshTheLists() {
  const queryClient = useQueryClient()

  return () => {
    void queryClient.invalidateQueries({ queryKey: queryKeys.contactList })
    void queryClient.invalidateQueries({ queryKey: queryKeys.organizationContacts })
  }
}

/** Every intention returns the fresh contact: it goes straight into the detail cache. */
function useStoreContact() {
  const queryClient = useQueryClient()
  const refreshTheLists = useRefreshTheLists()
  const { t } = useTranslation()

  return (contact: Contact | undefined) => {
    if (!contact) {
      return
    }
    queryClient.setQueryData(contactQuery(contact.id).queryKey, contact)
    refreshTheLists()
    toast.success(t('common.saved'))
  }
}

const formIntention = { meta: { silentValidation: true } }

export function useRenameContact() {
  return api.useMutation('patch', '/api/contacts/{id}/name', {
    ...formIntention,
    onSuccess: useStoreContact(),
  })
}

export function useChangeJobTitle() {
  return api.useMutation('patch', '/api/contacts/{id}/job-title', {
    ...formIntention,
    onSuccess: useStoreContact(),
  })
}

export function useChangeContactDetailsOfContact() {
  return api.useMutation('patch', '/api/contacts/{id}/contact-details', {
    ...formIntention,
    onSuccess: useStoreContact(),
  })
}

export function useForgetContact() {
  const queryClient = useQueryClient()
  const refreshTheLists = useRefreshTheLists()

  return api.useMutation('delete', '/api/contacts/{id}', {
    onSuccess: (_nothing, { params }) => {
      queryClient.removeQueries({ queryKey: contactQuery(params.path.id).queryKey })
      refreshTheLists()
      void queryClient.invalidateQueries({ queryKey: queryKeys.organizationList })
      readTheExchangesAgain(queryClient)
    },
  })
}
