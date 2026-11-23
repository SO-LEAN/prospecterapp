import { z } from 'zod'
import { text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'

/** What looks like a phone number, as the contact forms check it. The API checks the number itself. */
const PHONE_NUMBER = /^\+?[\d\s.()-]{6,}$/
const MAX_SUBJECT = 1000

const required = text(255).min(1, { error: () => i18n.t('validation.required') })

/** Mirrors the API's `BookingInput`: who books, as they describe themselves. Only the subject is optional. */
export const bookingSchema = z.object({
  firstName: required,
  lastName: required,
  phone: required.refine((value) => PHONE_NUMBER.test(value), {
    error: () => i18n.t('validation.phoneNumber'),
  }),
  email: required.pipe(z.email({ error: () => i18n.t('validation.email') })),
  subject: text(MAX_SUBJECT),
  website: z.string(),
})

export type BookingValues = z.input<typeof bookingSchema>

export const bookingFields = ['firstName', 'lastName', 'phone', 'email', 'subject'] as const

export const noBooking: BookingValues = {
  firstName: '',
  lastName: '',
  phone: '',
  email: '',
  subject: '',
  website: '',
}
