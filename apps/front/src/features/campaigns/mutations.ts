import { useQueryClient } from '@tanstack/react-query'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { timelineKeys } from '@/features/exchanges/queries'
import { api } from '@/shared/api/client'
import { queryKeys } from '@/shared/api/keys'
import { readAgainAfterTheReactions } from '@/shared/api/reactions'
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

  return api.useMutation('post', '/api/campaigns/{id}/freeze', {
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

  return api.useMutation('post', '/api/campaigns/{id}/start', {
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

  return api.useMutation('post', '/api/campaigns/{id}/stop', {
    onSuccess: async (campaign) => {
      await recipientsChanged(campaign)
      toast.success(t('campaigns.stop.done'))
    },
  })
}

/**
 * The exchange of a recipient noted in the campaign: Campaigns processes them once the reactions ran, reacting to the
 * facts of the exchange (ADR 0145), so the page moves on at once; the campaign, its recipients, the list and the
 * figures are read now and again once the reactions ran, the timelines now.
 */
export function useNoteCampaignExchange(campaignId: string) {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/campaigns/{id}/recipients/{contactId}/exchanges', {
    meta: { silentValidation: true },
    onSuccess: () => {
      readAgainAfterTheReactions(
        queryClient,
        campaignQuery(campaignId).queryKey,
        campaignKeys.recipients,
        campaignKeys.list,
        queryKeys.dashboard,
      )
      void queryClient.invalidateQueries({ queryKey: timelineKeys.ofContacts })
      void queryClient.invalidateQueries({ queryKey: timelineKeys.ofOrganizations })
      toast.success(t('campaigns.run.noted'))
    },
  })
}

/** The cap answered with the campaign: it is read again on the reader's clock, with the list. */
export function useSetDailyCap() {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('put', '/api/campaigns/{id}/daily-cap', {
    onSuccess: async (campaign) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: campaignQuery(campaign.id).queryKey }),
        queryClient.invalidateQueries({ queryKey: campaignKeys.list }),
      ])
      toast.success(t('campaigns.dailyCap.saved'))
    },
  })
}

/** Dismissed within the gesture: the campaign, its recipients and the list are read again at once. */
export function useDismissRecipient(campaignId: string) {
  const queryClient = useQueryClient()
  const { t } = useTranslation()

  return api.useMutation('post', '/api/campaigns/{id}/recipients/{contactId}/dismissal', {
    meta: { silentValidation: true },
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: campaignQuery(campaignId).queryKey }),
        queryClient.invalidateQueries({ queryKey: campaignKeys.recipients }),
        queryClient.invalidateQueries({ queryKey: campaignKeys.list }),
      ])
      toast.success(t('campaigns.run.dismissed'))
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
