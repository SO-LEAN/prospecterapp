import { z } from 'zod'
import { i18n } from '@/shared/i18n'

export const text = (max: number) =>
  z
    .string()
    .trim()
    .max(max, { error: () => i18n.t('validation.max', { max }) })

export const optionalEmail = z.union([z.literal(''), z.email({ error: () => i18n.t('validation.email') })])

/** What looks like a phone number: digits, possibly `+` first and separators; the API checks the number itself. */
const PHONE_NUMBER = /^\+?[\d\s.()-]{6,}$/

export const optionalPhoneNumber = text(255).refine((value) => value === '' || PHONE_NUMBER.test(value), {
  error: () => i18n.t('validation.phoneNumber'),
})

/** An empty field is "not provided" for the API, not an empty value. */
export const orNull = (value: string) => (value === '' ? null : value)
