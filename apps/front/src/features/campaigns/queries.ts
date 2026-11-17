import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'

export type Campaign = components['schemas']['CampaignResource']
export type CampaignList = components['schemas']['CampaignListResource']
export type CampaignInput = components['schemas']['CampaignInput']
export type CampaignMessage = components['schemas']['CampaignMessageResource']
export type Channel = Campaign['channel']
export type TargetedContact = components['schemas']['ContactInListResource']

/** The campaigns of the team, read whole, each with its target counted (ADR 0144). */
export const campaignsQuery = () => api.queryOptions('get', '/api/campaigns')

export const campaignQuery = (campaignId: string) =>
  api.queryOptions('get', '/api/campaigns/{id}', { params: { path: { id: campaignId } } })

/** The message of the campaign as saved, rendered by the api for one contact: the front never renders it. */
export const campaignMessageQuery = (campaignId: string, contactId: string) =>
  api.queryOptions('get', '/api/campaigns/{id}/messages/{contactId}', {
    params: { path: { id: campaignId, contactId } },
  })

export const campaignKeys = {
  list: ['get', '/api/campaigns'],
  messages: ['get', '/api/campaigns/{id}/messages/{contactId}'],
} as const
