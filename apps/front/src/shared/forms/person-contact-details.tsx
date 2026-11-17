import type { FieldValues } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import type { z } from 'zod'
import type { components } from '@/shared/api/schema'
import { Input } from '@/shared/ui/input'
import {
  ContactDetailsFields,
  contactDetailsFields,
  contactDetailsSchema,
  toContactDetails,
  toContactDetailsValues,
} from './contact-details'
import { FormField } from './form-field'
import { atRoot, type FieldsProps } from './section-form'
import { orNull, text } from './text'

type PersonContactDetailsResource = components['schemas']['PersonContactDetailsResource']
type PersonContactDetailsInput = components['schemas']['PersonContactDetailsInput']

/** The contact details of a person: those of an organization, and their profile on LinkedIn, checked by the API. */
export const personContactDetailsSchema = contactDetailsSchema.extend({ linkedInProfile: text(255) })

export type PersonContactDetailsValues = z.infer<typeof personContactDetailsSchema>

export const personContactDetailsFields = [...contactDetailsFields, 'linkedInProfile'] as const

export const toPersonContactDetailsValues = (
  contact: PersonContactDetailsResource,
): PersonContactDetailsValues => ({
  ...toContactDetailsValues(contact),
  linkedInProfile: contact.linkedInProfile ?? '',
})

export const toPersonContactDetails = (values: PersonContactDetailsValues): PersonContactDetailsInput => ({
  ...toContactDetails(values),
  linkedInProfile: orNull(values.linkedInProfile),
})

export function PersonContactDetailsFields<Values extends FieldValues>({
  form,
  path = atRoot,
}: FieldsProps<Values>) {
  const { t } = useTranslation()

  return (
    <>
      <ContactDetailsFields form={form} path={path} />
      <FormField
        form={form}
        name={path('linkedInProfile')}
        label={t('fields.linkedInProfile')}
        optional={t('common.optional')}
        hint={t('fields.linkedInProfileHint')}
      >
        <Input inputMode="url" autoComplete="url" {...form.register(path('linkedInProfile'))} />
      </FormField>
    </>
  )
}
