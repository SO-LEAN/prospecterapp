import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { ContactDetailsFields } from '@/shared/forms/contact-details'
import { FormField } from '@/shared/forms/form-field'
import { PersonNameFields } from '@/shared/forms/person-name'
import { under } from '@/shared/forms/section-form'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { Input } from '@/shared/ui/input'
import type { CollectContactInput } from '../queries'
import {
  type CollectContactValues,
  collectContactFields,
  collectContactSchema,
  emptyCollectContact,
  toCollectContactInput,
} from '../schema'

type Props = {
  onSubmit: (input: CollectContactInput) => Promise<unknown>
  onCancel: () => void
}

export function CollectContactForm({ onSubmit, onCancel }: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<CollectContactValues>({
    resolver: zodResolver(collectContactSchema),
    defaultValues: emptyCollectContact(),
  })
  const { isSubmitting, isDirty } = form.formState

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await onSubmit(toCollectContactInput(values))
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, collectContactFields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-6">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}

      <Card>
        <CardHeader>
          <CardTitle className="text-base">{t('contacts.sections.identity')}</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <PersonNameFields form={form} path={under<CollectContactValues>('name')} />
          <FormField
            form={form}
            name="jobTitle"
            label={t('fields.jobTitle')}
            optional={t('common.optional')}
            className="sm:col-span-2"
          >
            <Input autoComplete="organization-title" {...form.register('jobTitle')} />
          </FormField>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle className="text-base">{t('contacts.sections.contactDetails')}</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <ContactDetailsFields form={form} path={under<CollectContactValues>('contactDetails')} />
        </CardContent>
      </Card>

      <div className="flex justify-end gap-2">
        <Button type="button" variant="ghost" onClick={onCancel}>
          {t('common.cancel')}
        </Button>
        <Button type="submit" disabled={isSubmitting || !isDirty}>
          {isSubmitting ? t('common.saving') : t('contacts.collect.submit')}
        </Button>
      </div>
    </form>
  )
}
