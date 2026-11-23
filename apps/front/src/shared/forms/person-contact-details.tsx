import type { FieldValues } from 'react-hook-form'
import type { z } from 'zod'
import type { components } from '@/shared/api/schema'
import {
  ContactDetailsFields,
  contactDetailsFields,
  contactDetailsSchema,
  toContactDetails,
  toContactDetailsValues,
} from './contact-details'
import { LinksField, linksSchema } from './links-field'
import { atRoot, type FieldsProps } from './section-form'

type PersonContactDetailsResource = components['schemas']['PersonContactDetailsResource']
type PersonContactDetailsInput = components['schemas']['PersonContactDetailsInput']

/** A person's contact details: an organization's details, plus their links to outside tools, which the API checks. */
export const personContactDetailsSchema = contactDetailsSchema.extend({ links: linksSchema })

export type PersonContactDetailsValues = z.infer<typeof personContactDetailsSchema>

export const personContactDetailsFields = [...contactDetailsFields, 'links'] as const

export const toPersonContactDetailsValues = (
  contact: PersonContactDetailsResource,
): PersonContactDetailsValues => ({
  ...toContactDetailsValues(contact),
  links: contact.links.map((link) => link.address),
})

export const toPersonContactDetails = (values: PersonContactDetailsValues): PersonContactDetailsInput => ({
  ...toContactDetails(values),
  links: values.links,
})

export function PersonContactDetailsFields<Values extends FieldValues>({
  form,
  path = atRoot,
}: FieldsProps<Values>) {
  return (
    <>
      <ContactDetailsFields form={form} path={path} />
      <LinksField form={form} name={path('links')} />
    </>
  )
}
