import type { DefaultValues } from 'react-hook-form'
import { z } from 'zod'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import { CHANNELS, DIRECTIONS, type ExchangeDetail, type ExchangeFacts, type TextCorrection } from './queries'

const MAX_TEXT = 5000

/** "2026-10-18T19:00", the value of a `datetime-local` input, in the time of the browser. */
export const localMoment = (moment: Date) => {
  const local = new Date(moment.getTime() - moment.getTimezoneOffset() * 60_000)
  return local.toISOString().slice(0, 16)
}

const isPast = (moment: string) => new Date(moment).getTime() <= Date.now()

/** Mirrors `ExchangeInput` and `CorrectionInput` of the API: only a call is missed, never in the future. */
export const exchangeSchema = z.object({
  channel: z.enum(CHANNELS, { error: () => i18n.t('exchanges.chooseChannel') }),
  direction: z.enum(DIRECTIONS),
  occurredAt: z
    .string()
    .min(1, { error: () => i18n.t('validation.required') })
    .refine(isPast, { error: () => i18n.t('exchanges.inTheFuture') }),
  text: text(MAX_TEXT),
  missed: z.boolean(),
  personId: z.string(),
  madeConform: z.boolean(),
})

export type ExchangeValues = z.input<typeof exchangeSchema>

export const exchangeFields = [
  'channel',
  'direction',
  'occurredAt',
  'text',
  'missed',
  'personId',
  'madeConform',
] as const

/** The interlocutor chosen when noting from the card of an organization: none is the organization itself. */
export const ITSELF = ''

/** Quick to note: received, now; only the channel is to choose. */
export const newExchange = (now: Date): DefaultValues<ExchangeValues> => ({
  direction: 'inbound',
  occurredAt: localMoment(now),
  text: '',
  missed: false,
  personId: ITSELF,
  madeConform: false,
})

/** The exchange as it is, to correct it. */
export const correctionOf = (exchange: ExchangeDetail): ExchangeValues => ({
  channel: exchange.channel,
  direction: exchange.direction,
  occurredAt: localMoment(new Date(exchange.occurredAt)),
  text: exchange.text ?? '',
  missed: exchange.missed,
  personId: ITSELF,
  madeConform: false,
})

export const toExchangeFacts = (values: ExchangeValues): ExchangeFacts => ({
  channel: values.channel,
  direction: values.direction,
  occurredAt: new Date(values.occurredAt).toISOString(),
  text: orNull(values.text),
  missed: values.channel === 'call' && values.missed,
  ...(values.madeConform && { madeConform: true }),
})

export const personIdOf = (values: ExchangeValues): string | null =>
  values.personId === ITSELF ? null : values.personId

/** Mirrors `TextCorrectionInput` of the API. */
export const textSchema = z.object({ text: text(MAX_TEXT), madeConform: z.boolean() })

export type TextValues = z.input<typeof textSchema>

export const textFields = ['text', 'madeConform'] as const

export const toTextCorrection = (values: TextValues): TextCorrection => ({
  text: orNull(values.text),
  madeConform: values.madeConform,
})
