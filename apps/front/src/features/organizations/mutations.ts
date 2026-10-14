import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api, fetchClient } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import { fileBody } from '@/shared/api/upload'
import { type Organization, organizationQuery } from './queries'

/**
 * Every mutation returns the fresh organization: it goes straight into the detail cache
 * (no refetch), while lists and the dashboard are marked stale and refetched when displayed.
 */
function useStoreOrganization() {
  const queryClient = useQueryClient()

  return (organization: Organization | undefined) => {
    if (!organization) {
      return
    }
    queryClient.setQueryData(organizationQuery(organization.id).queryKey, organization)
    void queryClient.invalidateQueries({ queryKey: queryKeys.organizationList })
    void queryClient.invalidateQueries({ queryKey: queryKeys.dashboard })
  }
}

export function useCreateOrganization() {
  const store = useStoreOrganization()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/organizations', {
    meta: { silentValidation: true },
    onSuccess: (organization) => {
      store(organization)
      toast.success(t('organizations.create.success'))
    },
  })
}

export function useReplaceOrganization() {
  const store = useStoreOrganization()
  const { t } = useTranslation()

  return api.useMutation('put', '/api/organizations/{id}', {
    meta: { silentValidation: true },
    onSuccess: (organization) => {
      store(organization)
      toast.success(t('organizations.edit.success'))
    },
  })
}

export function useUploadOrganizationLogo(id: number) {
  const store = useStoreOrganization()
  const { t } = useTranslation()

  return useMutation({
    mutationFn: async (file: File) => {
      const { data, error } = await fetchClient.POST('/api/organizations/{id}/logo', {
        params: { path: { id: String(id) } },
        ...fileBody(file),
      })
      if (error || !data) {
        throw error
      }
      return data
    },
    onSuccess: (organization) => {
      store(organization)
      toast.success(t('organizations.logo.success'))
    },
  })
}

export function useRemoveOrganizationLogo() {
  const store = useStoreOrganization()
  const { t } = useTranslation()

  return api.useMutation('delete', '/api/organizations/{id}/logo', {
    onSuccess: (organization) => {
      store(organization)
      toast.success(t('organizations.logo.removed'))
    },
  })
}
