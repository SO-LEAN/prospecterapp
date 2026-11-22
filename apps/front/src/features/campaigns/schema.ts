import { z } from 'zod'
import { text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import type { Campaign, CampaignInput, Channel, MessageMode } from './queries'

const MAX_NAME = 100
const MAX_MESSAGE = 5000

export const CHANNELS = ['email', 'call', 'sms', 'linkedIn'] as const satisfies readonly Channel[]

/** How an AI leading the campaign treats its message: sent as rendered, or adapted to each person. */
export const MESSAGE_MODES = ['asIs', 'adapted'] as const satisfies readonly MessageMode[]

const required = () => i18n.t('validation.required')

/** Mirrors `CampaignInput` of the API; the core decides the rest, the tag of the channel among the 5 included. */
export const campaignSchema = z.object({
  name: text(MAX_NAME).min(1, { error: required }),
  channel: z.enum(CHANNELS),
  tags: z.array(z.string()).min(1, { error: required }),
  message: z.string().max(MAX_MESSAGE, { error: () => i18n.t('validation.max', { max: MAX_MESSAGE }) }),
  subject: z.string(),
  messageMode: z.enum(MESSAGE_MODES),
})

export type CampaignValues = z.input<typeof campaignSchema>

export const campaignFields = ['name', 'channel', 'tags', 'message', 'subject', 'messageMode'] as const

export const newCampaign = (): CampaignValues => ({
  name: '',
  channel: 'email',
  tags: [],
  message: '',
  subject: '',
  messageMode: 'asIs',
})

/** The campaign as saved, to revise it. */
export const revisionOf = (campaign: Campaign): CampaignValues => ({
  name: campaign.name,
  channel: campaign.channel,
  tags: campaign.tags,
  message: campaign.message,
  subject: campaign.subject ?? '',
  messageMode: campaign.messageMode,
})

/** Only an e-mail has a subject: one typed before choosing another channel is not sent. */
export const toCampaignInput = (values: CampaignValues): CampaignInput => ({
  name: values.name.trim(),
  channel: values.channel,
  tags: values.tags,
  message: values.message,
  subject: values.channel === 'email' && values.subject.trim() !== '' ? values.subject.trim() : null,
  messageMode: values.messageMode,
  dailyCap: null,
})

/** The automatic tag a contact carries when the channel reaches them (ADR 0144), as the api names them. */
const TAG_OF_THE_CHANNEL: Record<Channel, string> = {
  email: '#EMAIL',
  call: '#PHONE',
  sms: '#SMS',
  linkedIn: '#LINKEDIN',
}

/** The most tags a search takes at once, the tag of the channel among them (ADR 0143, 0144). */
export const MAX_TARGETED_TAGS = 5

/** The tags a contact carries to be targeted while the campaign is typed: those chosen and that of its channel. */
export const targetedTagsOf = (tags: string[], channel: Channel): string[] => [
  ...new Set([...tags, TAG_OF_THE_CHANNEL[channel]]),
]
