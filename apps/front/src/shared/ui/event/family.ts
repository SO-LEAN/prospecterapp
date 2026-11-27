import type { Channel } from './channel'

/** The tint an exchange takes from its channel. The written channels share one tint and differ by their icon. */
export type ExchangeFamily = 'call' | 'meeting' | 'video' | 'written'

export const FAMILY_OF_CHANNEL: Record<Channel, ExchangeFamily> = {
  call: 'call',
  meeting: 'meeting',
  videoCall: 'video',
  email: 'written',
  sms: 'written',
  linkedIn: 'written',
  malt: 'written',
  other: 'written',
}

/** Each family's soft background and its tint on it. */
export const SOFT_TINTS: Record<ExchangeFamily, string> = {
  call: 'bg-event-call-soft text-event-call',
  meeting: 'bg-event-meeting-soft text-event-meeting',
  video: 'bg-event-video-soft text-event-video',
  written: 'bg-event-written-soft text-event-written',
}
