import { type QueryClient, useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api, fetchClient } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import type { components } from '@/shared/api/schema'
import { fileBody } from '@/shared/api/upload'
import { accountQuery } from './queries'

export type Account = components['schemas']['AccountResource']

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

export function useChangeMyLoginEmail() {
  return api.useMutation('patch', '/api/me/login-email', { ...formIntention, onSuccess: useStoreAccount() })
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

export function useChangeMyPassword() {
  const { t } = useTranslation()

  return api.useMutation('patch', '/api/me/password', {
    ...formIntention,
    onSuccess: () => toast.success(t('common.saved')),
  })
}

function useSendAccountFile(path: '/api/me/picture' | '/api/me/organization/logo') {
  const store = useStoreAccount()

  return useMutation({
    mutationFn: async (file: File) => {
      const { data, error } = await fetchClient.POST(path, fileBody(file))
      if (error || !data) {
        throw error
      }
      return data
    },
    onSuccess: store,
  })
}

export const useChangeMyPicture = () => useSendAccountFile('/api/me/picture')
export const useChangeMyOrganizationLogo = () => useSendAccountFile('/api/me/organization/logo')
