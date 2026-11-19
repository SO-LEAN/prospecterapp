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
import { atRoot, type FieldsProps } from '@/shared/forms/section-form'
import { orNull, text } from '@/shared/forms/text'
import { Input } from '@/shared/ui/input'

type OrganizationContactDetailsResource = components['schemas']['OrganizationContactDetailsResource']
type OrganizationContactDetailsInput = components['schemas']['OrganizationContactDetailsInput']

/** The contact details of an organization: those of a person, the page of the company on LinkedIn and its website, checked by the API. */
export const organizationContactDetailsSchema = contactDetailsSchema.extend({
  linkedInPage: text(255),
  website: text(255),
})

export type OrganizationContactDetailsValues = z.infer<typeof organizationContactDetailsSchema>

export const organizationContactDetailsFields = [...contactDetailsFields, 'linkedInPage', 'website'] as const

export const toOrganizationContactDetailsValues = (
  contact: Partial<OrganizationContactDetailsResource>,
): OrganizationContactDetailsValues => ({
  ...toContactDetailsValues(contact),
  linkedInPage: contact.linkedInPage ?? '',
  website: contact.website ?? '',
})

export const toOrganizationContactDetails = (
  values: OrganizationContactDetailsValues,
): OrganizationContactDetailsInput => ({
  ...toContactDetails(values),
  linkedInPage: orNull(values.linkedInPage),
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
      <FormField
        form={form}
        name={path('linkedInPage')}
        label={t('fields.linkedInPage')}
        optional={t('common.optional')}
      >
        <Input inputMode="url" autoComplete="url" {...form.register(path('linkedInPage'))} />
      </FormField>
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
