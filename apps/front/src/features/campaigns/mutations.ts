import { useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { api } from '@/shared/api/client'
import { type Campaign, campaignKeys, campaignQuery } from './queries'

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

/** Frozen, the campaign answers its recipients counted: they are read again with the list. */
export function useFreezeCampaign() {
  const { t } = useTranslation()
  const recipientsChanged = useRecipientsChanged()

  return api.useMutation('post', '/api/campaigns/{id}/freezing', {
    onSuccess: async (campaign) => {
      await recipientsChanged(campaign)
      toast.success(t('campaigns.freeze.done'))
    },
  })
}

export function useAddRecipient() {
  const { t } = useTranslation()
  const recipientsChanged = useRecipientsChanged()

  return api.useMutation('post', '/api/campaigns/{id}/recipients', {
    onSuccess: async (campaign) => {
      await recipientsChanged(campaign)
      toast.success(t('campaigns.recipients.added'))
    },
  })
}

export function useAddRecipientsByTags() {
  const { t } = useTranslation()
  const recipientsChanged = useRecipientsChanged()

  return api.useMutation('post', '/api/campaigns/{id}/recipients/by-tags', {
    onSuccess: async (campaign) => {
      await recipientsChanged(campaign)
      toast.success(t('campaigns.recipients.added'))
    },
  })
}

/** Answered without the campaign: it is read again, its recipients counted. */
export function useRemoveRecipient(campaignId: string) {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('delete', '/api/campaigns/{id}/recipients/{contactId}', {
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: campaignQuery(campaignId).queryKey }),
        queryClient.invalidateQueries({ queryKey: campaignKeys.recipients }),
        queryClient.invalidateQueries({ queryKey: campaignKeys.list }),
      ])
      toast.success(t('campaigns.recipients.removed'))
    },
  })
}

/** Started, the campaign no longer changes: it answers itself, read again with the list and its recipients. */
export function useStartCampaign() {
  const { t } = useTranslation()
  const recipientsChanged = useRecipientsChanged()

  return api.useMutation('post', '/api/campaigns/{id}/starting', {
    onSuccess: async (campaign) => {
      await recipientsChanged(campaign)
      toast.success(t('campaigns.start.done'))
    },
  })
}

/** Stopped, the campaign stays listed: it answers itself, read again with the list. */
export function useStopCampaign() {
  const { t } = useTranslation()
  const recipientsChanged = useRecipientsChanged()

  return api.useMutation('post', '/api/campaigns/{id}/stopping', {
    onSuccess: async (campaign) => {
      await recipientsChanged(campaign)
      toast.success(t('campaigns.stop.done'))
    },
  })
}

function useRecipientsChanged() {
  const queryClient = useQueryClient()

  return async (campaign: Campaign) => {
    queryClient.setQueryData(campaignQuery(campaign.id).queryKey, campaign)
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: campaignKeys.recipients }),
      queryClient.invalidateQueries({ queryKey: campaignKeys.list }),
    ])
  }
}
