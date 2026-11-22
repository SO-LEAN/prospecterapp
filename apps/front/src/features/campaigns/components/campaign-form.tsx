import { zodResolver } from '@hookform/resolvers/zod'
import { type ReactNode, useEffect, useState } from 'react'
import { Controller, useForm, useWatch } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shared/ui/select'
import { Textarea } from '@/shared/ui/textarea'
import type { CampaignInput, Channel } from '../queries'
import {
  type CampaignValues,
  CHANNELS,
  campaignFields,
  campaignSchema,
  MESSAGE_MODES,
  toCampaignInput,
} from '../schema'

type Props = {
  values: CampaignValues
  submitLabel: string
  onSubmit: (input: CampaignInput) => Promise<unknown>
  onCancel?: () => void
  /** The field choosing the tags, given by the page: the filter of the tags of the account. */
  tagsField: (chosen: string[], onChange: (tags: string[]) => void) => ReactNode
  /** Told whether the form differs from the campaign as saved. */
  onChanged?: (changed: boolean) => void
  /** Told the tags and the channel as they are typed, for the target to follow them; none once frozen. */
  onTargetingChange?: (tags: string[], channel: Channel) => void
  /** Frozen, its channel and its tags are shown without being changed. */
  searchFrozen?: boolean
  /** As it is prepared, its daily cap; set on its sheet afterwards. */
  withDailyCap?: boolean
}

/**
 * A campaign as the member leaves it: its name, its channel, the tags of the contacts it targets and its message, the
 * variables of the message explained; the subject only for an e-mail.
 */
export function CampaignForm({
  values,
  submitLabel,
  onSubmit,
  onCancel,
  tagsField,
  onChanged,
  onTargetingChange,
  searchFrozen = false,
  withDailyCap = false,
}: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<CampaignValues>({ resolver: zodResolver(campaignSchema), values })
  const { isSubmitting, isDirty } = form.formState
  const channel = useWatch({ control: form.control, name: 'channel' })
  const tags = useWatch({ control: form.control, name: 'tags' })

  useEffect(() => onChanged?.(isDirty), [isDirty, onChanged])
  useEffect(() => onTargetingChange?.(tags, channel), [tags, channel, onTargetingChange])

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
            <OptionSelect
              value={field.value}
              options={CHANNELS}
              labelOf={(option) => t(`exchanges.channels.${option}`)}
              onChange={field.onChange}
              disabled={searchFrozen}
            />
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
            hint={t(searchFrozen ? 'campaigns.fields.tagsFrozen' : 'campaigns.fields.tagsHint')}
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
      <Controller
        control={form.control}
        name="messageMode"
        render={({ field }) => (
          <FormField
            form={form}
            name="messageMode"
            label={t('campaigns.fields.messageMode')}
            hint={t('campaigns.fields.messageModeHint')}
          >
            <OptionSelect
              value={field.value}
              options={MESSAGE_MODES}
              labelOf={(mode) => t(`campaigns.fields.messageModes.${mode}`)}
              onChange={field.onChange}
            />
          </FormField>
        )}
      />
      {withDailyCap && (
        <FormField
          form={form}
          name="dailyCap"
          label={t('campaigns.dailyCap.label')}
          hint={t('campaigns.dailyCap.hint')}
          optional={t('common.optional')}
        >
          <Input type="number" min={1} inputMode="numeric" className="w-32" {...form.register('dailyCap')} />
        </FormField>
      )}
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

type OptionProps<Option extends string> = {
  id?: string
  value: Option
  options: readonly Option[]
  labelOf: (option: Option) => string
  onChange: (option: Option) => void
  disabled?: boolean
  'aria-describedby'?: string
}

/** One of the options, the props of its field given to its trigger, the control its label names. */
function OptionSelect<Option extends string>({
  value,
  options,
  labelOf,
  onChange,
  disabled,
  ...trigger
}: OptionProps<Option>) {
  const chosen = (named: string) => {
    const option = options.find((offered) => offered === named)
    if (option) {
      onChange(option)
    }
  }

  return (
    <Select value={value} disabled={disabled} onValueChange={chosen}>
      <SelectTrigger className="w-full" {...trigger}>
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        {options.map((option) => (
          <SelectItem key={option} value={option}>
            {labelOf(option)}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  )
}
