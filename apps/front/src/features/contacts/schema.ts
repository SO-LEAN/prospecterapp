import { z } from 'zod'
import { contactDetailsSchema, toContactDetails } from '@/shared/forms/contact-details'
import { personNameSchema, toPersonName } from '@/shared/forms/person-name'
import { orNull, text } from '@/shared/forms/text'
import type { CollectContactInput } from './queries'

export const jobTitleSchema = z.object({ jobTitle: text(100) })

export const collectContactSchema = z.object({
  name: personNameSchema,
  jobTitle: text(100),
  contactDetails: contactDetailsSchema,
})

export type CollectContactValues = z.infer<typeof collectContactSchema>

/** Field paths, as the API reports them in its violations. */
export const collectContactFields = [
  'name.firstName',
  'name.lastName',
  'jobTitle',
  'contactDetails.email',
  'contactDetails.phoneNumber',
  'contactDetails.language',
] as const

export const emptyCollectContact = (): CollectContactValues => ({
  name: { firstName: '', lastName: '' },
  jobTitle: '',
  contactDetails: { email: '', phoneNumber: '', language: null },
})

export const toCollectContactInput = (values: CollectContactValues): CollectContactInput => ({
  name: toPersonName(values.name),
  jobTitle: orNull(values.jobTitle),
  contactDetails: toContactDetails(values.contactDetails),
})
