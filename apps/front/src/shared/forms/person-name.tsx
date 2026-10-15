import type { FieldValues } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import type { components } from '@/shared/api/schema'
import { Input } from '@/shared/ui/input'
import { FormField } from './form-field'
import { atRoot, type FieldsProps } from './section-form'
import { orNull, text } from './text'

type PersonNameResource = components['schemas']['PersonNameResource']
type PersonNameInput = components['schemas']['PersonNameInput']

export const personNameSchema = z.object({ firstName: text(100), lastName: text(100) })

export type PersonNameValues = z.infer<typeof personNameSchema>

export const personNameFields = ['firstName', 'lastName'] as const

export const toPersonNameValues = (name: PersonNameResource): PersonNameValues => ({
  firstName: name.firstName ?? '',
  lastName: name.lastName ?? '',
})

export const toPersonName = (values: PersonNameValues): PersonNameInput => ({
  firstName: orNull(values.firstName),
  lastName: orNull(values.lastName),
})

export function PersonNameFields<Values extends FieldValues>({ form, path = atRoot }: FieldsProps<Values>) {
  const { t } = useTranslation()

  return (
    <>
      <FormField form={form} name={path('firstName')} label={t('fields.firstName')}>
        <Input autoComplete="given-name" {...form.register(path('firstName'))} />
      </FormField>
      <FormField form={form} name={path('lastName')} label={t('fields.lastName')}>
        <Input autoComplete="family-name" {...form.register(path('lastName'))} />
      </FormField>
    </>
  )
}
