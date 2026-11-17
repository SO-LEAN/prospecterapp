import { useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api } from '@/shared/api/client'
import { campaignKeys, campaignQuery } from './queries'

export function usePrepareCampaign() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/campaigns', {
    meta: { silentValidation: true },
    onSuccess: async (campaign) => {
      queryClient.setQueryData(campaignQuery(campaign.id).queryKey, campaign)
      await queryClient.invalidateQueries({ queryKey: campaignKeys.list })
      toast.success(t('campaigns.prepared'))
    },
  })
}

/** The message of the campaign changes with it: its previews are read again. */
export function useReviseCampaign() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('patch', '/api/campaigns/{id}', {
    meta: { silentValidation: true },
    onSuccess: async (campaign) => {
      queryClient.setQueryData(campaignQuery(campaign.id).queryKey, campaign)
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: campaignKeys.list }),
        queryClient.invalidateQueries({ queryKey: campaignKeys.messages }),
      ])
      toast.success(t('common.saved'))
    },
  })
}

export function useDiscardCampaign() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('delete', '/api/campaigns/{id}', {
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: campaignKeys.list })
      toast.success(t('campaigns.discarded'))
    },
  })
}
