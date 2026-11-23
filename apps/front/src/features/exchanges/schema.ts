import type { DefaultValues } from 'react-hook-form'
import { z } from 'zod'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import {
  CHANNELS,
  DIRECTIONS,
  type ExchangeDetail,
  type ExchangeFacts,
  isAgreedUpon,
  type Report,
  type TextCorrection,
} from './queries'

const MAX_TEXT = 5000

/** "2026-10-18T19:00": a `datetime-local` input's value, in the browser's time zone. */
export const localMoment = (moment: Date) => {
  const local = new Date(moment.getTime() - moment.getTimezoneOffset() * 60_000)
  return local.toISOString().slice(0, 16)
}

const isPast = (moment: string) => new Date(moment).getTime() <= Date.now()

/**
 * Mirrors the API's `ExchangeInput` and `CorrectionInput`. Only a call, a meeting or a video call can be missed. An
 * exchange is never in the future.
 */
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
  redacted: z.boolean(),
})

export type ExchangeValues = z.input<typeof exchangeSchema>

export const exchangeFields = [
  'channel',
  'direction',
  'occurredAt',
  'text',
  'missed',
  'personId',
  'redacted',
] as const

/** The interlocutor chosen when logging from an organization's card. None means the organization itself. */
export const ITSELF = ''

/** Quick to log: received, now. Only the channel remains to choose. */
export const newExchange = (now: Date): DefaultValues<ExchangeValues> => ({
  direction: 'inbound',
  occurredAt: localMoment(now),
  text: '',
  missed: false,
  personId: ITSELF,
  redacted: false,
})

/** The exchange as it currently is, to correct it. */
export const correctionOf = (exchange: ExchangeDetail): ExchangeValues => ({
  channel: exchange.channel,
  direction: exchange.direction,
  occurredAt: localMoment(new Date(exchange.occurredAt)),
  text: exchange.text ?? '',
  missed: exchange.status === 'missed',
  personId: ITSELF,
  redacted: false,
})

export const toExchangeFacts = (values: ExchangeValues): ExchangeFacts => ({
  channel: values.channel,
  direction: values.direction,
  occurredAt: new Date(values.occurredAt).toISOString(),
  text: orNull(values.text),
  missed: isAgreedUpon(values.channel) && values.missed,
  ...(values.redacted && { redacted: true }),
})

export const personIdOf = (values: ExchangeValues): string | null =>
  values.personId === ITSELF ? null : values.personId

/** Mirrors the API's `ReportInput`: when the exchange really took place, never in the future, and what was said. */
export const reportSchema = z.object({
  occurredAt: z
    .string()
    .min(1, { error: () => i18n.t('validation.required') })
    .refine(isPast, { error: () => i18n.t('exchanges.inTheFuture') }),
  text: text(MAX_TEXT),
})

export type ReportValues = z.input<typeof reportSchema>

export const reportFields = ['occurredAt', 'text'] as const

/** The exchange's planned moment and its agenda, which the report starts from. */
export const reportOf = (exchange: ExchangeDetail): ReportValues => ({
  occurredAt: localMoment(new Date(exchange.occurredAt)),
  text: exchange.text ?? '',
})

export const toReport = (values: ReportValues): Report => ({
  occurredAt: new Date(values.occurredAt).toISOString(),
  text: orNull(values.text),
})

/** Mirrors the API's `TextCorrectionInput`. */
export const textSchema = z.object({ text: text(MAX_TEXT), redacted: z.boolean() })

export type TextValues = z.input<typeof textSchema>

export const textFields = ['text', 'redacted'] as const

export const toTextCorrection = (values: TextValues): TextCorrection => ({
  text: orNull(values.text),
  redacted: values.redacted,
})
