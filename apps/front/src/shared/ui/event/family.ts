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

/** The red edge of what is late, an exchange or a to-do. */
export const LATE_EDGE = 'border-l-4 border-destructive'

/** Each family's tint, on the text. */
export const TINTS: Record<ExchangeFamily, string> = {
  call: 'text-event-call',
  meeting: 'text-event-meeting',
  video: 'text-event-video',
  written: 'text-event-written',
}

/** Each family's tint as a solid fill, the text white on it. */
export const SOLID_TINTS: Record<ExchangeFamily, string> = {
  call: 'bg-event-call text-primary-foreground',
  meeting: 'bg-event-meeting text-primary-foreground',
  video: 'bg-event-video text-primary-foreground',
  written: 'bg-event-written text-primary-foreground',
}

/** Each family's soft background and its tint on it. */
export const SOFT_TINTS: Record<ExchangeFamily, string> = {
  call: 'bg-event-call-soft text-event-call',
  meeting: 'bg-event-meeting-soft text-event-meeting',
  video: 'bg-event-video-soft text-event-video',
  written: 'bg-event-written-soft text-event-written',
}
