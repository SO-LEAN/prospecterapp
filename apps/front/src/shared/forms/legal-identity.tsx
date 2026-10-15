import type { FieldValues } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import type { components } from '@/shared/api/schema'
import { Input } from '@/shared/ui/input'
import { FormField } from './form-field'
import { atRoot, type FieldsProps } from './section-form'
import { orNull, text } from './text'

type LegalIdentityResource = components['schemas']['LegalIdentityResource']
type LegalIdentityInput = components['schemas']['LegalIdentityInput']

export const legalIdentitySchema = z.object({ corporateName: text(150), form: text(50), type: text(50) })

export type LegalIdentityValues = z.infer<typeof legalIdentitySchema>

export const legalIdentityFields = ['corporateName', 'form', 'type'] as const

export const toLegalIdentityValues = (identity: LegalIdentityResource): LegalIdentityValues => ({
  corporateName: identity.corporateName ?? '',
  form: identity.form ?? '',
  type: identity.type ?? '',
})

export const toLegalIdentity = (values: LegalIdentityValues): LegalIdentityInput => ({
  corporateName: orNull(values.corporateName),
  form: orNull(values.form),
  type: orNull(values.type),
})

export function LegalIdentityFields<Values extends FieldValues>({
  form,
  path = atRoot,
  withType = true,
}: FieldsProps<Values> & { withType?: boolean }) {
  const { t } = useTranslation()

  return (
    <>
      <FormField
        form={form}
        name={path('corporateName')}
        label={t('fields.corporateName')}
        className="sm:col-span-2"
      >
        <Input {...form.register(path('corporateName'))} />
      </FormField>
      <FormField form={form} name={path('form')} label={t('fields.form')} optional={t('common.optional')}>
        <Input {...form.register(path('form'))} placeholder="SAS, SARL…" />
      </FormField>
      {withType && (
        <FormField form={form} name={path('type')} label={t('fields.type')} optional={t('common.optional')}>
          <Input {...form.register(path('type'))} />
        </FormField>
      )}
    </>
  )
}
