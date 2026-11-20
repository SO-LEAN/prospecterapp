import { z } from 'zod'
import { text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'

/** What looks like a phone number, as the forms of the contacts check it: the API checks the number itself. */
const PHONE_NUMBER = /^\+?[\d\s.()-]{6,}$/
const MAX_SUBJECT = 1000

const required = text(255).min(1, { error: () => i18n.t('validation.required') })

/** Mirrors `BookingInput` of the API: who books, as they say it, the subject only may be left out. */
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
