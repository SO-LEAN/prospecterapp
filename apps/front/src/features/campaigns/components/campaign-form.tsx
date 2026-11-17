import { zodResolver } from '@hookform/resolvers/zod'
import { type ReactNode, useEffect, useState } from 'react'
import { Controller, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shared/ui/select'
import { Textarea } from '@/shared/ui/textarea'
import type { CampaignInput } from '../queries'
import { type CampaignValues, CHANNELS, campaignFields, campaignSchema, toCampaignInput } from '../schema'

type Props = {
  values: CampaignValues
  submitLabel: string
  onSubmit: (input: CampaignInput) => Promise<unknown>
  onCancel?: () => void
  /** The field choosing the tags, given by the page: the filter of the tags of the account. */
  tagsField: (chosen: string[], onChange: (tags: string[]) => void) => ReactNode
  /** Told whether the form differs from the campaign as saved. */
  onChanged?: (changed: boolean) => void
}

/**
 * A campaign as the member leaves it: its name, its channel, the tags of the contacts it targets and its message, the
 * variables of the message explained; the subject only for an e-mail.
 */
export function CampaignForm({ values, submitLabel, onSubmit, onCancel, tagsField, onChanged }: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<CampaignValues>({ resolver: zodResolver(campaignSchema), values })
  const { isSubmitting, isDirty } = form.formState
  const channel = form.watch('channel')

  useEffect(() => onChanged?.(isDirty), [isDirty, onChanged])

  const submit = form.handleSubmit(async (submitted) => {
    setGlobalErrors([])
    try {
      await onSubmit(toCampaignInput(submitted))
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, campaignFields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-4">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}
      <FormField form={form} name="name" label={t('campaigns.fields.name')}>
        <Input {...form.register('name')} />
      </FormField>
      <Controller
        control={form.control}
        name="channel"
        render={({ field }) => (
          <FormField form={form} name="channel" label={t('campaigns.fields.channel')}>
            <Select value={field.value} onValueChange={field.onChange}>
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {CHANNELS.map((option) => (
                  <SelectItem key={option} value={option}>
                    {t(`campaigns.channels.${option}`)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </FormField>
        )}
      />
      <Controller
        control={form.control}
        name="tags"
        render={({ field }) => (
          <FormField
            form={form}
            name="tags"
            label={t('campaigns.fields.tags')}
            hint={t('campaigns.fields.tagsHint')}
          >
            <div>{tagsField(field.value, field.onChange)}</div>
          </FormField>
        )}
      />
      {channel === 'email' && (
        <FormField
          form={form}
          name="subject"
          label={t('campaigns.fields.subject')}
          optional={t('common.optional')}
        >
          <Input {...form.register('subject')} />
        </FormField>
      )}
      <FormField
        form={form}
        name="message"
        label={t('campaigns.fields.message')}
        hint={t('campaigns.fields.messageHint')}
      >
        <Textarea rows={8} {...form.register('message')} />
      </FormField>
      <div className="flex justify-end gap-2">
        {onCancel && (
          <Button type="button" variant="ghost" onClick={onCancel}>
            {t('common.cancel')}
          </Button>
        )}
        <Button type="submit" disabled={isSubmitting || !isDirty}>
          {isSubmitting ? t('common.saving') : submitLabel}
        </Button>
      </div>
    </form>
  )
}
