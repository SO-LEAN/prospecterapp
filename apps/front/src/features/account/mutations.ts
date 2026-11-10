import { type QueryClient, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api, fetchClient } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import type { components } from '@/shared/api/schema'
import { fileBody } from '@/shared/api/upload'
import { accountQuery, signInQuery } from './queries'

export type Account = components['schemas']['AccountResource']
type PersonNameInput = components['schemas']['PersonNameInput']
type ContactDetailsInput = components['schemas']['ContactDetailsInput']

const refreshTheDashboardShowingMyOrganization = (queryClient: QueryClient) =>
  queryClient.invalidateQueries({ queryKey: queryKeys.dashboard })

function useStoreAccount() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return (account: Account | undefined) => {
    if (!account) {
      return
    }
    queryClient.setQueryData(accountQuery().queryKey, account)
    void refreshTheDashboardShowingMyOrganization(queryClient)
    toast.success(t('common.saved'))
  }
}

const formIntention = { meta: { silentValidation: true } }

export function useChangeMyName() {
  return api.useMutation('patch', '/api/me/name', { ...formIntention, onSuccess: useStoreAccount() })
}

/** What a member gives of themselves to complete their profile: their name and the email of their profile. */
export type MyProfile = { name: PersonNameInput; contactDetails: ContactDetailsInput }

/**
 * Completes the profile of the signed-in member with the intentions of their account, each its own use case: the
 * email first, the one an API may refuse (already the email of another member), then the name, which shows the
 * application once stored.
 */
export function useCompleteMyProfile() {
  const queryClient = useQueryClient()

  return useMutation({
    ...formIntention,
    mutationFn: async ({ name, contactDetails }: MyProfile) => {
      answerOf(await fetchClient.PATCH('/api/me/contact-details', { body: contactDetails }))
      return answerOf(await fetchClient.PATCH('/api/me/name', { body: name }))
    },
    onSuccess: (account) => queryClient.setQueryData(accountQuery().queryKey, account),
  })
}

function answerOf<Answer>({ data, error }: { data?: Answer; error?: unknown }): Answer {
  if (error || !data) {
    throw error
  }
  return data
}

export function useChangeMyLoginEmail() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('patch', '/api/me/sign-in/login-email', {
    ...formIntention,
    onSuccess: (signIn) => {
      queryClient.setQueryData(signInQuery().queryKey, signIn)
      toast.success(t('common.saved'))
    },
  })
}

export function useChangeMyContactDetails() {
  return api.useMutation('patch', '/api/me/contact-details', {
    ...formIntention,
    onSuccess: useStoreAccount(),
  })
}

export function useReviseMyOrganizationIdentity() {
  return api.useMutation('patch', '/api/me/organization/legal-identity', {
    ...formIntention,
    onSuccess: useStoreAccount(),
  })
}

export function useRelocateMyOrganization() {
  return api.useMutation('patch', '/api/me/organization/address', {
    ...formIntention,
    onSuccess: useStoreAccount(),
  })
}

export function useChangeMyPassword() {
  const { t } = useTranslation()

  return api.useMutation('patch', '/api/me/sign-in/password', {
    ...formIntention,
    onSuccess: () => toast.success(t('common.saved')),
  })
}

function useSendAccountFile(path: '/api/me/picture' | '/api/me/organization/logo') {
  const store = useStoreAccount()

  return useMutation({
    mutationFn: async (file: File) => answerOf(await fetchClient.POST(path, fileBody(file))),
    onSuccess: store,
  })
}

export const useChangeMyPicture = () => useSendAccountFile('/api/me/picture')
export const useChangeMyOrganizationLogo = () => useSendAccountFile('/api/me/organization/logo')
