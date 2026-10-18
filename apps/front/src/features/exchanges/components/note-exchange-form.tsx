import { zodResolver } from '@hookform/resolvers/zod'
import { ArrowDownLeft, ArrowUpRight, type LucideIcon } from 'lucide-react'
import { type ReactNode, useState } from 'react'
import { type UseFormRegisterReturn, useForm, useFormState, useWatch } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { Textarea } from '@/shared/ui/textarea'
import { CHANNELS, DIRECTIONS, type ExchangeInput } from '../queries'
import {
  type ExchangeValues,
  exchangeFields,
  exchangeSchema,
  localMoment,
  newExchange,
  toExchangeInput,
} from '../schema'
import { CHANNEL_ICONS } from './exchange-line'

type Props = {
  onSubmit: (input: ExchangeInput) => Promise<unknown>
  onCancel: () => void
}

const DIRECTION_ICONS = { inbound: ArrowDownLeft, outbound: ArrowUpRight }

/** Noted in a few seconds: received and now by default, the channel to choose, then save. */
export function NoteExchangeForm({ onSubmit, onCancel }: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<ExchangeValues>({
    resolver: zodResolver(exchangeSchema),
    defaultValues: newExchange(new Date()),
  })
  const { errors, isSubmitting } = useFormState({ control: form.control })
  const channel = useWatch({ control: form.control, name: 'channel' })

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await onSubmit(toExchangeInput(values))
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, exchangeFields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-4">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}

      <Choices legend={t('exchanges.channel')} error={errors.channel?.message}>
        {CHANNELS.map((value) => (
          <Choice
            key={value}
            icon={CHANNEL_ICONS[value]}
            label={t(`exchanges.channels.${value}`)}
            value={value}
            {...form.register('channel')}
          />
        ))}
      </Choices>

      <Choices legend={t('exchanges.direction')}>
        {DIRECTIONS.map((value) => (
          <Choice
            key={value}
            icon={DIRECTION_ICONS[value]}
            label={t(`exchanges.directions.${value}`)}
            value={value}
            {...form.register('direction')}
          />
        ))}
      </Choices>

      {channel === 'call' && (
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" className="size-4 accent-primary" {...form.register('missed')} />
          {t('exchanges.missedLabel')}
        </label>
      )}

      <FormField form={form} name="occurredAt" label={t('exchanges.occurredAt')}>
        <Input type="datetime-local" max={localMoment(new Date())} {...form.register('occurredAt')} />
      </FormField>
      <FormField
        form={form}
        name="text"
        label={t('exchanges.text')}
        optional={t('common.optional')}
        hint={t('exchanges.textHint')}
      >
        <Textarea rows={3} {...form.register('text')} />
      </FormField>

      <div className="flex justify-end gap-2">
        <Button type="button" variant="ghost" onClick={onCancel}>
          {t('common.cancel')}
        </Button>
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? t('common.saving') : t('common.save')}
        </Button>
      </div>
    </form>
  )
}

function Choices({ legend, error, children }: { legend: string; error?: string; children: ReactNode }) {
  return (
    <fieldset className="space-y-2" aria-invalid={error ? true : undefined}>
      <legend className="font-medium text-sm">{legend}</legend>
      <div className="flex flex-wrap gap-2">{children}</div>
      {error && <p className="text-destructive text-xs">{error}</p>}
    </fieldset>
  )
}

type ChoiceProps = UseFormRegisterReturn & { icon: LucideIcon; label: string; value: string }

/** A radio button looking like a button: the input, transparent over its label, is what is clicked and read. */
function Choice({ icon: Icon, label, ...radio }: ChoiceProps) {
  return (
    <label className="relative inline-flex cursor-pointer items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-accent has-checked:border-primary has-checked:bg-primary has-checked:text-primary-foreground has-focus-visible:ring-[3px] has-focus-visible:ring-ring/50">
      <input type="radio" className="absolute inset-0 cursor-pointer appearance-none opacity-0" {...radio} />
      <Icon aria-hidden className="size-4" />
      {label}
    </label>
  )
}
