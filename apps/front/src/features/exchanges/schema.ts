import type { DefaultValues } from 'react-hook-form'
import { z } from 'zod'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import { CHANNELS, DIRECTIONS, type ExchangeInput } from './queries'

const MAX_TEXT = 5000

/** "2026-10-18T19:00", the value of a `datetime-local` input, in the time of the browser. */
export const localMoment = (moment: Date) => {
  const local = new Date(moment.getTime() - moment.getTimezoneOffset() * 60_000)
  return local.toISOString().slice(0, 16)
}

const isPast = (moment: string) => new Date(moment).getTime() <= Date.now()

/** Mirrors `ExchangeInput` of the API: only a call is missed, never in the future. */
export const exchangeSchema = z.object({
  channel: z.enum(CHANNELS, { error: () => i18n.t('exchanges.chooseChannel') }),
  direction: z.enum(DIRECTIONS),
  occurredAt: z
    .string()
    .min(1, { error: () => i18n.t('validation.required') })
    .refine(isPast, { error: () => i18n.t('exchanges.inTheFuture') }),
  text: text(MAX_TEXT),
  missed: z.boolean(),
})

export type ExchangeValues = z.input<typeof exchangeSchema>

export const exchangeFields = ['channel', 'direction', 'occurredAt', 'text', 'missed'] as const

/** Quick to note: received, now; only the channel is to choose. */
export const newExchange = (now: Date): DefaultValues<ExchangeValues> => ({
  direction: 'inbound',
  occurredAt: localMoment(now),
  text: '',
  missed: false,
})

export const toExchangeInput = (values: ExchangeValues): ExchangeInput => ({
  channel: values.channel,
  direction: values.direction,
  occurredAt: new Date(values.occurredAt).toISOString(),
  text: orNull(values.text),
  missed: values.channel === 'call' && values.missed,
})
