import type { FieldValues } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import type { components } from '@/shared/api/schema'
import { Input } from '@/shared/ui/input'
import { CountryField } from './country-field'
import { FormField } from './form-field'
import { atRoot, type FieldsProps } from './section-form'
import { optionalEmail, optionalPhoneNumber, orNull } from './text'

/** The contact details that a person, an organization and an account share. */
type ContactDetails = Pick<
  components['schemas']['PersonContactDetailsInput'],
  'email' | 'phoneNumber' | 'language'
>
type ContactDetailsRead = Partial<ContactDetails>

export const contactDetailsSchema = z.object({
  email: optionalEmail,
  phoneNumber: optionalPhoneNumber,
  language: z.string().nullable(),
})

export type ContactDetailsValues = z.infer<typeof contactDetailsSchema>

export const contactDetailsFields = ['email', 'phoneNumber', 'language'] as const

export const toContactDetailsValues = (contact: ContactDetailsRead): ContactDetailsValues => ({
  email: contact.email ?? '',
  phoneNumber: contact.phoneNumber ?? '',
  language: contact.language ?? null,
})

export const toContactDetails = (values: ContactDetailsValues): ContactDetails => ({
  email: orNull(values.email),
  phoneNumber: orNull(values.phoneNumber),
  language: values.language,
})

export function ContactDetailsFields<Values extends FieldValues>({
  form,
  path = atRoot,
}: FieldsProps<Values>) {
  const { t } = useTranslation()

  return (
    <>
      <FormField form={form} name={path('email')} label={t('fields.email')}>
        <Input type="email" autoComplete="email" {...form.register(path('email'))} />
      </FormField>
      <FormField
        form={form}
        name={path('phoneNumber')}
        label={t('fields.phoneNumber')}
        optional={t('common.optional')}
      >
        <Input type="tel" autoComplete="tel" {...form.register(path('phoneNumber'))} />
      </FormField>
      <CountryField
        form={form}
        name={path('language')}
        label={t('fields.language')}
        optional={t('common.optional')}
      />
    </>
  )
}
