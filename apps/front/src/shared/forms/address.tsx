import { Controller, type FieldValues } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import type { components } from '@/shared/api/schema'
import { CountrySelect } from '@/shared/ui/form/country-select'
import { Input } from '@/shared/ui/input'
import { FormField } from './form-field'
import { atRoot, type FieldsProps } from './section-form'
import { orNull, text } from './text'

type AddressResource = components['schemas']['AddressResource']
type AddressInput = components['schemas']['AddressInput']

export const addressSchema = z.object({
  street: text(255),
  postalCode: text(255),
  city: text(255),
  country: z.string().nullable(),
})

export type AddressValues = z.infer<typeof addressSchema>

export const addressFields = ['street', 'postalCode', 'city', 'country'] as const

export const toAddressValues = (address: AddressResource | null | undefined): AddressValues => ({
  street: address?.street ?? '',
  postalCode: address?.postalCode ?? '',
  city: address?.city ?? '',
  country: address?.country ?? null,
})

export const toAddress = (values: AddressValues): AddressInput => ({
  street: orNull(values.street),
  postalCode: orNull(values.postalCode),
  city: orNull(values.city),
  country: values.country,
})

export function AddressFields<Values extends FieldValues>({ form, path = atRoot }: FieldsProps<Values>) {
  const { t } = useTranslation()

  return (
    <>
      <FormField form={form} name={path('street')} label={t('fields.street')} className="sm:col-span-2">
        <Input autoComplete="off" {...form.register(path('street'))} />
      </FormField>
      <FormField form={form} name={path('postalCode')} label={t('fields.postalCode')}>
        <Input {...form.register(path('postalCode'))} />
      </FormField>
      <FormField form={form} name={path('city')} label={t('fields.city')}>
        <Input {...form.register(path('city'))} />
      </FormField>
      <FormField form={form} name={path('country')} label={t('fields.country')}>
        <Controller
          control={form.control}
          name={path('country')}
          render={({ field: { value, onChange } }) => (
            <CountrySelect value={value as string | null} onChange={onChange} />
          )}
        />
      </FormField>
    </>
  )
}
