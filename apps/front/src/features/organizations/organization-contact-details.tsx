import type { FieldValues } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import type { z } from 'zod'
import type { components } from '@/shared/api/schema'
import {
  ContactDetailsFields,
  contactDetailsFields,
  contactDetailsSchema,
  toContactDetails,
  toContactDetailsValues,
} from '@/shared/forms/contact-details'
import { FormField } from '@/shared/forms/form-field'
import { LinksField, linksSchema } from '@/shared/forms/links-field'
import { atRoot, type FieldsProps } from '@/shared/forms/section-form'
import { orNull, text } from '@/shared/forms/text'
import { Input } from '@/shared/ui/input'

type OrganizationContactDetailsResource = components['schemas']['OrganizationContactDetailsResource']
type OrganizationContactDetailsInput = components['schemas']['OrganizationContactDetailsInput']

/** An organization's contact details: a person's fields, its links to outside tools and its website. The API checks them. */
export const organizationContactDetailsSchema = contactDetailsSchema.extend({
  links: linksSchema,
  website: text(255),
})

export type OrganizationContactDetailsValues = z.infer<typeof organizationContactDetailsSchema>

export const organizationContactDetailsFields = [...contactDetailsFields, 'links', 'website'] as const

export const toOrganizationContactDetailsValues = (
  contact: Partial<OrganizationContactDetailsResource>,
): OrganizationContactDetailsValues => ({
  ...toContactDetailsValues(contact),
  links: contact.links?.map((link) => link.address) ?? [],
  website: contact.website ?? '',
})

export const toOrganizationContactDetails = (
  values: OrganizationContactDetailsValues,
): OrganizationContactDetailsInput => ({
  ...toContactDetails(values),
  links: values.links,
  website: orNull(values.website),
})

export function OrganizationContactDetailsFields<Values extends FieldValues>({
  form,
  path = atRoot,
}: FieldsProps<Values>) {
  const { t } = useTranslation()

  return (
    <>
      <ContactDetailsFields form={form} path={path} />
      <LinksField form={form} name={path('links')} />
      <FormField
        form={form}
        name={path('website')}
        label={t('fields.website')}
        optional={t('common.optional')}
      >
        <Input inputMode="url" autoComplete="url" {...form.register(path('website'))} />
      </FormField>
    </>
  )
}
