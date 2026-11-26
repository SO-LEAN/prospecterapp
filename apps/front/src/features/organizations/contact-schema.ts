import { z } from 'zod'
import { civilitySchema, UNKNOWN_CIVILITY } from '@/shared/forms/civility'
import { personContactDetailsSchema, toPersonContactDetails } from '@/shared/forms/person-contact-details'
import { personNameSchema, toPersonName } from '@/shared/forms/person-name'
import { orNull, text } from '@/shared/forms/text'
import { i18n } from '@/shared/i18n'
import type { CollectContactInput } from './queries'

/** A new contact for its organization. A contact never comes without an organization (ADR 0042). */
export const collectContactSchema = z.object({
  organizationId: z.string().min(1, { error: () => i18n.t('validation.organization') }),
  name: personNameSchema,
  title: civilitySchema,
  jobTitle: text(100),
  contactDetails: personContactDetailsSchema,
})

export type CollectContactValues = z.infer<typeof collectContactSchema>

/** The field paths that the API's violations report. */
export const collectContactFields = [
  'name.firstName',
  'name.lastName',
  'title',
  'jobTitle',
  'contactDetails.email',
  'contactDetails.phoneNumber',
  'contactDetails.language',
  'contactDetails.links',
] as const

export const emptyCollectContact = (organizationId = ''): CollectContactValues => ({
  organizationId,
  name: { firstName: '', lastName: '' },
  title: UNKNOWN_CIVILITY,
  jobTitle: '',
  contactDetails: { email: '', phoneNumber: '', language: null, links: [] },
})

export const toCollectContactInput = (values: CollectContactValues): CollectContactInput => ({
  name: toPersonName(values.name),
  title: values.title,
  jobTitle: orNull(values.jobTitle),
  contactDetails: toPersonContactDetails(values.contactDetails),
  organizationIds: [values.organizationId],
})
