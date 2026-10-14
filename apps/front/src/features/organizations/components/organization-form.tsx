import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { Controller, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shared/ui/card'
import { CountrySelect } from '@/shared/ui/form/country-select'
import { Field } from '@/shared/ui/form/field'
import { Input } from '@/shared/ui/input'
import { Textarea } from '@/shared/ui/textarea'
import type { OrganizationInput } from '../queries'
import {
  type OrganizationField,
  type OrganizationFormValues,
  organizationFields,
  organizationSchema,
  toInput,
} from '../schema'

type Props = {
  defaultValues: OrganizationFormValues
  submitLabel: string
  onSubmit: (input: OrganizationInput) => Promise<unknown>
  onCancel: () => void
}

export function OrganizationForm({ defaultValues, submitLabel, onSubmit, onCancel }: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const { register, control, handleSubmit, setError, getFieldState, formState } =
    useForm<OrganizationFormValues>({ resolver: zodResolver(organizationSchema), defaultValues })

  const { isSubmitting, isDirty } = formState

  const submit = handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await onSubmit(toInput(values))
    } catch (error) {
      setGlobalErrors(applyViolations(error, setError, organizationFields))
    }
  })

  const field = (name: OrganizationField) => ({
    label: t(`organizations.fields.${name}`),
    error: getFieldState(name, formState).error?.message,
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-6">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}

      <Section title={t('organizations.sections.identity')}>
        <Field {...field('corporateName')} className="sm:col-span-2">
          <Input {...register('corporateName')} autoFocus />
        </Field>
        <Field {...field('form')} optional={t('common.optional')}>
          <Input {...register('form')} placeholder="SAS, SARL…" />
        </Field>
        <Field {...field('type')} optional={t('common.optional')}>
          <Input {...register('type')} />
        </Field>
      </Section>

      <Section title={t('organizations.sections.contact')}>
        <Field {...field('email')}>
          <Input type="email" autoComplete="off" {...register('email')} />
        </Field>
        <Field {...field('phoneNumber')} optional={t('common.optional')}>
          <Input type="tel" {...register('phoneNumber')} />
        </Field>
        <Field {...field('language')} optional={t('common.optional')}>
          <Controller
            control={control}
            name="language"
            render={({ field: { value, onChange } }) => <CountrySelect value={value} onChange={onChange} />}
          />
        </Field>
      </Section>

      <Section title={t('organizations.sections.address')}>
        <Field {...field('address.street')} className="sm:col-span-2">
          <Input autoComplete="off" {...register('address.street')} />
        </Field>
        <Field {...field('address.postalCode')}>
          <Input {...register('address.postalCode')} />
        </Field>
        <Field {...field('address.city')}>
          <Input {...register('address.city')} />
        </Field>
        <Field {...field('address.country')}>
          <Controller
            control={control}
            name="address.country"
            render={({ field: { value, onChange } }) => <CountrySelect value={value} onChange={onChange} />}
          />
        </Field>
      </Section>

      <Section title={t('organizations.sections.notes')}>
        <Field {...field('observations')} optional={t('common.optional')} className="sm:col-span-2">
          <Textarea rows={4} {...register('observations')} />
        </Field>
      </Section>

      <div className="sticky bottom-0 -mx-4 flex justify-end gap-2 border-t bg-background/80 px-4 py-4 backdrop-blur md:mx-0 md:rounded-xl md:border">
        <Button type="button" variant="ghost" onClick={onCancel}>
          {t('common.cancel')}
        </Button>
        <Button type="submit" disabled={isSubmitting || !isDirty}>
          {isSubmitting ? t('common.saving') : submitLabel}
        </Button>
      </div>
    </form>
  )
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">{title}</CardTitle>
        <CardDescription className="sr-only">{title}</CardDescription>
      </CardHeader>
      <CardContent className="grid gap-5 sm:grid-cols-2">{children}</CardContent>
    </Card>
  )
}
