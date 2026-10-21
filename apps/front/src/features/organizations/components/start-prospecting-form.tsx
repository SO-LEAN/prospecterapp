import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { AddressFields } from '@/shared/forms/address'
import { ContactDetailsFields } from '@/shared/forms/contact-details'
import { FormField } from '@/shared/forms/form-field'
import { LegalIdentityFields } from '@/shared/forms/legal-identity'
import { under } from '@/shared/forms/section-form'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/ui/card'
import { Textarea } from '@/shared/ui/textarea'
import type { StartProspectingInput } from '../queries'
import {
  emptyStartProspecting,
  type StartProspectingValues,
  startProspectingFields,
  startProspectingSchema,
  toStartProspectingInput,
} from '../schema'

type Props = {
  onSubmit: (input: StartProspectingInput) => Promise<unknown>
  onCancel: () => void
}

export function StartProspectingForm({ onSubmit, onCancel }: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<StartProspectingValues>({
    resolver: zodResolver(startProspectingSchema),
    defaultValues: emptyStartProspecting(),
  })
  const { isSubmitting, isDirty } = form.formState

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await onSubmit(toStartProspectingInput(values))
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, startProspectingFields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-6">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}

      <Section title={t('organizations.sections.legalIdentity')}>
        <LegalIdentityFields form={form} path={under<StartProspectingValues>('legalIdentity')} />
      </Section>
      <Section title={t('organizations.sections.contactDetails')}>
        <ContactDetailsFields form={form} path={under<StartProspectingValues>('contactDetails')} />
      </Section>
      <Section title={t('organizations.sections.address')}>
        <AddressFields form={form} path={under<StartProspectingValues>('address')} />
      </Section>
      <Section title={t('organizations.sections.observations')}>
        <FormField
          form={form}
          name="observations"
          label={t('fields.observations')}
          optional={t('common.optional')}
          className="sm:col-span-2"
        >
          <Textarea rows={4} {...form.register('observations')} />
        </FormField>
      </Section>

      <div className="sticky bottom-0 -mx-4 flex justify-end gap-2 border-t bg-background/80 px-4 py-4 backdrop-blur md:mx-0 md:rounded-xl md:border">
        <Button type="button" variant="ghost" onClick={onCancel}>
          {t('common.cancel')}
        </Button>
        <Button type="submit" disabled={isSubmitting || !isDirty}>
          {isSubmitting ? t('common.saving') : t('organizations.start.submit')}
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
      </CardHeader>
      <CardContent className="grid grid-cols-1 gap-5 sm:grid-cols-2">{children}</CardContent>
    </Card>
  )
}
