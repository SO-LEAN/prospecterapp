import { zodResolver } from '@hookform/resolvers/zod'
import { useQuery } from '@tanstack/react-query'
import { TriangleAlert } from 'lucide-react'
import { useState } from 'react'
import { type UseFormReturn, useForm, useFormState, useWatch } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { formatHour } from '@/shared/lib/format'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { Textarea } from '@/shared/ui/textarea'
import {
  dateAndTimeOf,
  endOf,
  newPlan,
  type PlanValues,
  planFields,
  planSchema,
  QUICK_DURATIONS,
  SHORTCUTS,
  slotOf,
  toPlannedExchange,
} from '../planning'
import { overlappingQuery, PLANNED_CHANNELS, type PlannedExchangeInput } from '../queries'
import { ITSELF } from '../schema'
import { Choice, Choices, type Interlocutors } from './exchange-form'
import { CHANNEL_ICONS, INTERLOCUTOR_ICONS } from './exchange-line'

type Props = {
  /** The person chosen, null for the organization itself or on the card of a contact. */
  onSubmit: (exchange: PlannedExchangeInput, personId: string | null) => Promise<unknown>
  onCancel: () => void
  interlocutors?: Interlocutors
}

/**
 * Planned in a few clicks: the next quarter of an hour and half an hour by default, shortcuts for the usual moments
 * and durations; warned, without being stopped, of an exchange of mine it overlaps.
 */
export function PlanForm({ onSubmit, onCancel, interlocutors }: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<PlanValues>({ resolver: zodResolver(planSchema), defaultValues: newPlan(new Date()) })
  const { errors, isSubmitting } = useFormState({ control: form.control })
  const setMoment = (moment: Date) => {
    const { date, time } = dateAndTimeOf(moment)
    form.setValue('date', date, { shouldValidate: true })
    form.setValue('time', time, { shouldValidate: true })
  }

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await onSubmit(toPlannedExchange(values), values.personId === ITSELF ? null : values.personId)
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, planFields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-4">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}

      {interlocutors && (
        <Choices legend={t('exchanges.interlocutor')} error={errors.personId?.message}>
          <Choice
            icon={INTERLOCUTOR_ICONS.organization}
            label={interlocutors.organization}
            value={ITSELF}
            {...form.register('personId')}
          />
          {interlocutors.contacts.map((contact) => (
            <Choice
              key={contact.id}
              icon={INTERLOCUTOR_ICONS.person}
              label={contact.name}
              value={contact.id}
              {...form.register('personId')}
            />
          ))}
        </Choices>
      )}

      <Choices legend={t('exchanges.channel')} error={errors.channel?.message}>
        {PLANNED_CHANNELS.map((value) => (
          <Choice
            key={value}
            icon={CHANNEL_ICONS[value]}
            label={t(`exchanges.channels.${value}`)}
            value={value}
            {...form.register('channel')}
          />
        ))}
      </Choices>

      <fieldset className="space-y-2">
        <legend className="font-medium text-sm">{t('exchanges.shortcuts')}</legend>
        <div className="flex flex-wrap gap-2">
          {(Object.keys(SHORTCUTS) as (keyof typeof SHORTCUTS)[]).map((shortcut) => (
            <Button
              key={shortcut}
              type="button"
              size="sm"
              variant="outline"
              onClick={() => setMoment(SHORTCUTS[shortcut](new Date()))}
            >
              {t(`exchanges.${shortcut}`)}
            </Button>
          ))}
        </div>
      </fieldset>

      <div className="grid grid-cols-2 gap-3">
        <FormField form={form} name="date" label={t('exchanges.date')}>
          <Input type="date" {...form.register('date')} />
        </FormField>
        <FormField form={form} name="time" label={t('exchanges.time')}>
          <Input type="time" step={900} {...form.register('time')} />
        </FormField>
      </div>

      <FormField form={form} name="durationInMinutes" label={t('exchanges.duration')}>
        <Input
          type="number"
          min={5}
          max={480}
          step={5}
          {...form.register('durationInMinutes', { valueAsNumber: true })}
        />
      </FormField>
      <div className="flex flex-wrap gap-2">
        {QUICK_DURATIONS.map((minutes) => (
          <Button
            key={minutes}
            type="button"
            size="sm"
            variant="outline"
            onClick={() => form.setValue('durationInMinutes', minutes, { shouldValidate: true })}
          >
            {t(`exchanges.durations.${minutes}`)}
          </Button>
        ))}
      </div>

      <Overlaps form={form} />

      <FormField form={form} name="text" label={t('exchanges.agenda')} optional={t('common.optional')}>
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

/** The exchanges I planned in the slot being chosen: a warning, the planning still possible. */
function Overlaps({ form }: { form: UseFormReturn<PlanValues> }) {
  const { t } = useTranslation()
  const [date, time, durationInMinutes] = useWatch({
    control: form.control,
    name: ['date', 'time', 'durationInMinutes'],
  })
  const slot = slotOf({ date, time, durationInMinutes })
  const overlapping = useQuery({
    ...overlappingQuery(slot?.startsAt ?? '', slot?.durationInMinutes ?? 0),
    enabled: slot !== null,
  })
  const items = slot ? (overlapping.data?.items ?? []) : []

  if (items.length === 0) {
    return null
  }

  return (
    <Alert>
      <TriangleAlert aria-hidden />
      <AlertDescription>
        {items.map(({ exchange, interlocutor }) => (
          <p key={exchange.id}>
            {t('exchanges.overlap', {
              exchange: t(`exchanges.anExchange.${exchange.channel}`),
              name: interlocutor.name ?? t('exchanges.unknownPerson'),
              from: formatHour(exchange.occurredAt),
              to: formatHour(endOf(exchange.occurredAt, exchange.durationInMinutes ?? 0)),
            })}
          </p>
        ))}
      </AlertDescription>
    </Alert>
  )
}
