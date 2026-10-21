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
import { Input } from '@/shared/ui/input'
import {
  type CollectContactValues,
  collectContactFields,
  collectContactSchema,
  emptyCollectContact,
  toCollectContactInput,
} from '../contact-schema'
import type { CollectContactInput } from '../queries'

type Props = {
  organizationId: string
  onSubmit: (input: CollectContactInput) => Promise<unknown>
  onCancel: () => void
}

/** A new contact, collected for this organization. */
export function CollectContactForm({ organizationId, onSubmit, onCancel }: Props) {
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
      await onSubmit(toCollectContactInput(values, organizationId))
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, collectContactFields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-4">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}

      <fieldset className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <legend className="mb-2 font-medium text-sm">{t('organizations.contacts.identity')}</legend>
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
      </fieldset>
      <fieldset className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <legend className="mb-2 font-medium text-sm">{t('organizations.sections.contactDetails')}</legend>
        <ContactDetailsFields form={form} path={under<CollectContactValues>('contactDetails')} />
      </fieldset>

      <div className="flex justify-end gap-2">
        <Button type="button" variant="ghost" onClick={onCancel}>
          {t('common.cancel')}
        </Button>
        <Button type="submit" disabled={isSubmitting || !isDirty}>
          {isSubmitting ? t('common.saving') : t('organizations.contacts.collectSubmit')}
        </Button>
      </div>
    </form>
  )
}
