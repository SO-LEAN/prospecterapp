import { z } from 'zod'
import { text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import type { Campaign, CampaignInput, Channel, MessageMode } from './queries'

const MAX_NAME = 100
const MAX_MESSAGE = 5000

export const CHANNELS = ['email', 'call', 'sms', 'linkedIn'] as const satisfies readonly Channel[]

/**
 * How an AI running the campaign treats its message: it sends it as rendered, or adapts it to each person.
 */
export const MESSAGE_MODES = ['asIs', 'adapted'] as const satisfies readonly MessageMode[]

const required = () => i18n.t('validation.required')

/**
 * Mirrors the API's `CampaignInput`. The core decides the rest, including that the channel's tag counts
 * within the 5 tags.
 */
export const campaignSchema = z.object({
  name: text(MAX_NAME).min(1, { error: required }),
  channel: z.enum(CHANNELS),
  tags: z.array(z.string()).min(1, { error: required }),
  message: z.string().max(MAX_MESSAGE, { error: () => i18n.t('validation.max', { max: MAX_MESSAGE }) }),
  subject: z.string(),
  messageMode: z.enum(MESSAGE_MODES),
  dailyCap: z.string(),
})

export type CampaignValues = z.input<typeof campaignSchema>

export const campaignFields = [
  'name',
  'channel',
  'tags',
  'message',
  'subject',
  'messageMode',
  'dailyCap',
] as const

export const newCampaign = (): CampaignValues => ({
  name: '',
  channel: 'email',
  tags: [],
  message: '',
  subject: '',
  messageMode: 'asIs',
  dailyCap: '',
})

/** The campaign as saved, to revise it. */
export const revisionOf = (campaign: Campaign): CampaignValues => ({
  name: campaign.name,
  channel: campaign.channel,
  tags: campaign.tags,
  message: campaign.message,
  subject: campaign.subject ?? '',
  messageMode: campaign.messageMode,
  dailyCap: campaign.dailyCap?.toString() ?? '',
})

/**
 * Only an e-mail has a subject. A subject typed before the member chose another channel is not sent. A daily
 * cap left empty means no cap.
 */
export const toCampaignInput = (values: CampaignValues): CampaignInput => ({
  name: values.name.trim(),
  channel: values.channel,
  tags: values.tags,
  message: values.message,
  subject: values.channel === 'email' && values.subject.trim() !== '' ? values.subject.trim() : null,
  messageMode: values.messageMode,
  dailyCap: values.dailyCap.trim() === '' ? null : Number(values.dailyCap),
})

/** The automatic tag that a contact carries when the channel reaches them (ADR 0144), as the API names it. */
const TAG_OF_THE_CHANNEL: Record<Channel, string> = {
  email: '#EMAIL',
  call: '#PHONE',
  sms: '#SMS',
  linkedIn: '#LINKEDIN',
}

/** The most tags a search takes at once, counting the channel's tag (ADR 0143, 0144). */
export const MAX_TARGETED_TAGS = 5

/** The tags a targeted contact carries while the campaign is typed: the chosen tags and the channel's tag. */
export const targetedTagsOf = (tags: string[], channel: Channel): string[] => [
  ...new Set([...tags, TAG_OF_THE_CHANNEL[channel]]),
]
