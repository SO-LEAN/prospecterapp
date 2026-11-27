import type { DefaultValues } from 'react-hook-form'
import { z } from 'zod'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import { type Moment, PLANNED_CHANNELS, type PlannedExchangeInput } from './queries'
import { ITSELF, localMoment } from './schema'
import { isTheLinkAWebAddress, linkRefused, venueFields, venueOf } from './venue'

const MAX_TEXT = 5000
const QUARTER_MS = 15 * 60_000
const SHORTEST = 5
/** The longest a planned exchange lasts, in minutes (ADR 0111). */
export const LONGEST = 8 * 60
const USUAL = 30
const NINE = 9
const MONDAY = 1
const WEEK = 7

/** The durations that one click sets, in minutes. */
export const QUICK_DURATIONS = [15, 30, 60, 120] as const

/** The next quarter hour. An exchange planned now starts then. */
export const nextQuarterHour = (now: Date) =>
  new Date(Math.ceil((now.getTime() + 1) / QUARTER_MS) * QUARTER_MS)

const atNine = (day: Date) => new Date(day.getFullYear(), day.getMonth(), day.getDate(), NINE)

const daysAfter = (day: Date, days: number) =>
  new Date(day.getFullYear(), day.getMonth(), day.getDate() + days, day.getHours(), day.getMinutes())

/** The moments that a shortcut sets: tomorrow at 9, next Monday at 9, and a week after the next quarter hour. */
export const SHORTCUTS = {
  tomorrowAt9: (now: Date) => atNine(daysAfter(now, 1)),
  mondayAt9: (now: Date) => atNine(daysAfter(now, ((MONDAY - now.getDay() + WEEK - 1) % WEEK) + 1)),
  inAWeek: (now: Date) => daysAfter(nextQuarterHour(now), WEEK),
} as const

const momentOf = (values: { date: string; time: string }) => new Date(`${values.date}T${values.time}`)

/**
 * Mirrors the API's `PlannedExchangeInput`: an upcoming call, meeting or video call, from 5 minutes to 8 hours long.
 * A meeting has a place. A video call has a link, which is a web address.
 */
export const planSchema = z
  .object({
    channel: z.enum(PLANNED_CHANNELS, { error: () => i18n.t('exchanges.chooseChannel') }),
    date: z.string().min(1, { error: () => i18n.t('validation.required') }),
    time: z.string().min(1, { error: () => i18n.t('validation.required') }),
    durationInMinutes: z
      .number({ error: () => i18n.t('exchanges.chooseDuration') })
      .int()
      .min(SHORTEST, { error: () => i18n.t('exchanges.chooseDuration') })
      .max(LONGEST, { error: () => i18n.t('exchanges.chooseDuration') }),
    text: text(MAX_TEXT),
    ...venueFields,
    meetLink: z.boolean(),
    personId: z.string(),
  })
  .refine((values) => momentOf(values).getTime() > Date.now(), {
    path: ['time'],
    error: () => i18n.t('exchanges.inThePast'),
  })
  .refine(isTheLinkAWebAddress, linkRefused)

export type PlanValues = z.input<typeof planSchema>

export const planFields = [
  'channel',
  'date',
  'time',
  'durationInMinutes',
  'text',
  'place',
  'link',
  'personId',
] as const

/**
 * Mirrors the API's `MomentInput`: another upcoming moment, and its duration. The API checks the duration's bounds, and
 * names it `duration` when it refuses it.
 */
export const moveSchema = z
  .object({
    date: z.string().min(1, { error: () => i18n.t('validation.required') }),
    time: z.string().min(1, { error: () => i18n.t('validation.required') }),
    duration: z.number({ error: () => i18n.t('exchanges.chooseDuration') }),
  })
  .refine((values) => momentOf(values).getTime() > Date.now(), {
    path: ['time'],
    error: () => i18n.t('exchanges.inThePast'),
  })

export type MoveValues = z.input<typeof moveSchema>

export const moveFields = ['date', 'time', 'duration'] as const

/** The new moment, and the duration only when it changes: the API keeps the duration it is not sent. */
export const toMoment = (values: MoveValues, currentDuration?: number | null): Moment => ({
  occurredAt: momentOf(values).toISOString(),
  durationInMinutes: values.duration === currentDuration ? null : values.duration,
})

/** The date and the time for a moment's inputs, in the browser's time zone. */
export const dateAndTimeOf = (moment: Date) => {
  const [date = '', time = ''] = localMoment(moment).split('T')
  return { date, time }
}

/** A moment and a duration from it: what the member chooses in the calendar to plan an exchange. */
export type Slot = { startsAt: Date; durationInMinutes: number }

/**
 * Quick to plan: the next quarter hour for half an hour, or the chosen slot. A meeting gets the proposed place, and a
 * video call gets a Meet link. Only the channel remains to choose.
 */
export const newPlan = (
  now: Date,
  proposedPlace = '',
  slot: Slot = { startsAt: nextQuarterHour(now), durationInMinutes: USUAL },
): DefaultValues<PlanValues> => ({
  ...dateAndTimeOf(slot.startsAt),
  durationInMinutes: slot.durationInMinutes,
  text: '',
  place: proposedPlace,
  link: '',
  meetLink: true,
  personId: ITSELF,
})

/** The slot of the moment that the values name, if any. The form warns of the exchanges it overlaps. */
export const slotOf = (values: Partial<PlanValues>) => {
  if (!values.date || !values.time) {
    return null
  }
  const minutes = Number(values.durationInMinutes)
  const startsAt = momentOf({ date: values.date, time: values.time })

  return Number.isNaN(startsAt.getTime()) || !(minutes >= SHORTEST && minutes <= LONGEST)
    ? null
    : { startsAt: startsAt.toISOString(), durationInMinutes: minutes }
}

/**
 * A Meet link is requested only for a video call without a link. Its box is offered while Google is connected
 * (ADR 0137).
 */
export const isAMeetLinkOffered = (values: Pick<PlanValues, 'channel' | 'link'>, googleConnected: boolean) =>
  googleConnected && values.channel === 'videoCall' && values.link.trim() === ''

export const toPlannedExchange = (values: PlanValues, googleConnected: boolean): PlannedExchangeInput => ({
  channel: values.channel,
  occurredAt: momentOf(values).toISOString(),
  durationInMinutes: Number(values.durationInMinutes),
  text: orNull(values.text),
  ...venueOf(values),
  linkRequested: values.meetLink && isAMeetLinkOffered(values, googleConnected),
})

/** The end of a planned exchange. */
export const endOf = (startsAt: string, durationInMinutes: number) =>
  new Date(new Date(startsAt).getTime() + durationInMinutes * 60_000).toISOString()
