import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api, fetchClient } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import type { components } from '@/shared/api/schema'
import { fileBody } from '@/shared/api/upload'
import { accountQuery } from './queries'

export type Account = components['schemas']['AccountResource']
export type AccountInput = components['schemas']['AccountInput']

function useStoreAccount() {
  const queryClient = useQueryClient()

  return (account: Account | undefined) => {
    if (!account) {
      return
    }
    queryClient.setQueryData(accountQuery().queryKey, account)
    // the organization name and logo are also shown on the dashboard
    void queryClient.invalidateQueries({ queryKey: queryKeys.dashboard })
  }
}

export function useReplaceAccount() {
  const store = useStoreAccount()

  return api.useMutation('put', '/api/me', { meta: { silentValidation: true }, onSuccess: store })
}

function useUploadAccountFile(
  path: '/api/me/picture' | '/api/me/organization-logo',
  successMessage: 'account.pictureSuccess' | 'account.logoSuccess',
) {
  const store = useStoreAccount()
  const { t } = useTranslation()

  return useMutation({
    mutationFn: async (file: File) => {
      const { data, error } = await fetchClient.POST(path, fileBody(file))
      if (error || !data) {
        throw error
      }
      return data
    },
    onSuccess: (account) => {
      store(account)
      toast.success(t(successMessage))
    },
  })
}

export const useUploadPicture = () => useUploadAccountFile('/api/me/picture', 'account.pictureSuccess')
export const useUploadOrganizationLogo = () =>
  useUploadAccountFile('/api/me/organization-logo', 'account.logoSuccess')
