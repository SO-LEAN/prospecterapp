import { api } from '@/shared/api/client'
import type { components } from '@/shared/api/schema'
import { readerTimeZone } from '@/shared/lib/time-zone'

export type Campaign = components['schemas']['CampaignResource']
export type CampaignList = components['schemas']['CampaignListResource']
export type CampaignInput = components['schemas']['CampaignInput']
export type CampaignMessage = components['schemas']['CampaignMessageResource']
export type Channel = Campaign['channel']
export type MessageMode = Campaign['messageMode']
export type TargetedContact = components['schemas']['ContactInListResource']
export type RecipientList = components['schemas']['RecipientListResource']
export type Recipient = components['schemas']['RecipientResource']

/** Every campaign of the team, each with its target count (ADR 0144). */
export const campaignsQuery = () => api.queryOptions('get', '/api/campaigns')

/** Read on the reader's clock. Its recipients processed today follow it. */
export const campaignQuery = (campaignId: string) =>
  api.queryOptions('get', '/api/campaigns/{id}', {
    params: { path: { id: campaignId }, query: { timeZone: readerTimeZone() } },
  })

/** Every recipient that a frozen campaign keeps, 500 at most. */
export const campaignRecipientsQuery = (campaignId: string) =>
  api.queryOptions('get', '/api/campaigns/{id}/recipients', { params: { path: { id: campaignId } } })

/** The saved campaign message, which the API renders for one contact. The front never renders it. */
export const campaignMessageQuery = (campaignId: string, contactId: string) =>
  api.queryOptions('get', '/api/campaigns/{id}/messages/{contactId}', {
    params: { path: { id: campaignId, contactId } },
  })

export const campaignKeys = {
  list: ['get', '/api/campaigns'],
  messages: ['get', '/api/campaigns/{id}/messages/{contactId}'],
  recipients: ['get', '/api/campaigns/{id}/recipients'],
} as const
