import { z } from 'zod'
import { text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import type { Campaign, CampaignInput, Channel } from './queries'

const MAX_NAME = 100
const MAX_MESSAGE = 5000

export const CHANNELS: Channel[] = ['email', 'call', 'sms', 'linkedIn']

const required = () => i18n.t('validation.required')

/** Mirrors `CampaignInput` of the API; the core decides the rest, the tag of the channel among the 5 included. */
export const campaignSchema = z.object({
  name: text(MAX_NAME).min(1, { error: required }),
  channel: z.enum(['email', 'call', 'sms', 'linkedIn']),
  tags: z.array(z.string()).min(1, { error: required }),
  message: z.string().max(MAX_MESSAGE, { error: () => i18n.t('validation.max', { max: MAX_MESSAGE }) }),
  subject: z.string(),
})

export type CampaignValues = z.input<typeof campaignSchema>

export const campaignFields = ['name', 'channel', 'tags', 'message', 'subject'] as const

export const newCampaign = (): CampaignValues => ({
  name: '',
  channel: 'email',
  tags: [],
  message: '',
  subject: '',
})

/** The campaign as saved, to revise it. */
export const revisionOf = (campaign: Campaign): CampaignValues => ({
  name: campaign.name,
  channel: campaign.channel,
  tags: campaign.tags,
  message: campaign.message,
  subject: campaign.subject ?? '',
})

/** Only an e-mail has a subject: one typed before choosing another channel is not sent. */
export const toCampaignInput = (values: CampaignValues): CampaignInput => ({
  name: values.name.trim(),
  channel: values.channel,
  tags: values.tags,
  message: values.message,
  subject: values.channel === 'email' && values.subject.trim() !== '' ? values.subject.trim() : null,
})
