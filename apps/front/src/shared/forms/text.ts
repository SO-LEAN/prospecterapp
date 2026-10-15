import { z } from 'zod'
import { i18n } from '@/shared/i18n'

export const text = (max: number) =>
  z
    .string()
    .trim()
    .max(max, { error: () => i18n.t('validation.max', { max }) })

export const optionalEmail = z.union([z.literal(''), z.email({ error: () => i18n.t('validation.email') })])

/** An empty field is "not provided" for the API, not an empty value. */
export const orNull = (value: string) => (value === '' ? null : value)
