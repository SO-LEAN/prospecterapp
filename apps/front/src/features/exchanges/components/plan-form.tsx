import { zodResolver } from '@hookform/resolvers/zod'
import { useQuery } from '@tanstack/react-query'
import { TriangleAlert } from 'lucide-react'
import { useMemo, useState } from 'react'
import { type UseFormReturn, useForm, useFormState, useWatch } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { useDebouncedValue } from '@/shared/hooks/use-debounced-value'
import { formatHour } from '@/shared/lib/format'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { Textarea } from '@/shared/ui/textarea'
import {
  dateAndTimeOf,
  endOf,
  isAMeetLinkOffered,
  newPlan,
  type PlanValues,
  planFields,
  planSchema,
  SHORTCUTS,
  type Slot,
  slotOf,
  toPlannedExchange,
} from '../planning'
import {
  isGoogleConnected,
  myConnectionsQuery,
  overlappingQuery,
  PLANNED_CHANNELS,
  type PlannedChannel,
  type PlannedExchangeInput,
} from '../queries'
import { ITSELF } from '../schema'
import { Choice, Choices, type Interlocutors } from './exchange-form'
import { CHANNEL_ICONS, INTERLOCUTOR_ICONS } from './exchange-line'
import { QuickDurations } from './quick-durations'
import { VideoCallLinkInput } from './video-call-link-input'

type Props = {
  /** Gets the chosen person, null for the organization itself or on a contact's card. */
  onSubmit: (exchange: PlannedExchangeInput, personId: string | null) => Promise<unknown>
  onCancel: () => void
  interlocutors?: Interlocutors
  /** The proposed channel: the previous exchange's channel. */
  channel?: PlannedChannel
  /** Where the form proposes to hold a meeting: the organization's address, written on one line. */
  proposedPlace?: string
  /** The proposed slot, chosen in the calendar, instead of the next quarter hour for half an hour. */
  slot?: Slot
}

/**
 * Plans an exchange in a few clicks. By default it starts at the next quarter hour and lasts half an hour. Shortcuts
 * set the usual moments and durations, and a meeting gets a proposed place. The form warns me of an exchange of mine
 * that it overlaps, without stopping me.
 */
export function PlanForm({ onSubmit, onCancel, interlocutors, channel, proposedPlace, slot }: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<PlanValues>({
    resolver: zodResolver(planSchema),
    defaultValues: { ...newPlan(new Date(), proposedPlace, slot), channel },
  })
  const { errors, isSubmitting } = useFormState({ control: form.control })
  const googleConnected = isGoogleConnected(useQuery(myConnectionsQuery()).data)
  const setMoment = (moment: Date) => {
    const { date, time } = dateAndTimeOf(moment)
    form.setValue('date', date, { shouldValidate: true })
    form.setValue('time', time, { shouldValidate: true })
  }

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await onSubmit(
        toPlannedExchange(values, googleConnected),
        values.personId === ITSELF ? null : values.personId,
      )
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

      <Venue form={form} />
      <MeetLink form={form} googleConnected={googleConnected} />

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
      <PlanDurations form={form} />

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

/**
 * Where a meeting is held, written freely. Or a video call's optional link. When a whole invitation is pasted, the
 * field takes its first Meet, Teams or Zoom link.
 */
function Venue({ form }: { form: UseFormReturn<PlanValues> }) {
  const { t } = useTranslation()
  const channel = useWatch({ control: form.control, name: 'channel' })

  if (channel === 'meeting') {
    return (
      <FormField form={form} name="place" label={t('exchanges.place')} optional={t('common.optional')}>
        <Input autoComplete="off" {...form.register('place')} />
      </FormField>
    )
  }

  if (channel === 'videoCall') {
    return (
      <FormField
        form={form}
        name="link"
        label={t('exchanges.link')}
        optional={t('common.optional')}
        hint={t('exchanges.linkHint')}
      >
        <VideoCallLinkInput
          {...form.register('link')}
          onLink={(link) => form.setValue('link', link, { shouldValidate: true })}
        />
      </FormField>
    )
  }

  return null
}

/** Creates a Google Meet link for a video call without a link, while Google is connected. The box is checked by default. */
function MeetLink({ form, googleConnected }: { form: UseFormReturn<PlanValues>; googleConnected: boolean }) {
  const { t } = useTranslation()
  const [channel, link] = useWatch({ control: form.control, name: ['channel', 'link'] })

  if (!isAMeetLinkOffered({ channel, link: link ?? '' }, googleConnected)) {
    return null
  }

  return (
    <label className="flex items-center gap-2 text-sm">
      <input type="checkbox" className="size-4 accent-primary" {...form.register('meetLink')} />
      {t('exchanges.meetLink')}
    </label>
  )
}

/** The usual durations, one click each, for the exchange being planned. */
function PlanDurations({ form }: { form: UseFormReturn<PlanValues> }) {
  const durationInMinutes = useWatch({ control: form.control, name: 'durationInMinutes' })

  return (
    <QuickDurations
      chosen={durationInMinutes}
      onChoose={(minutes) => form.setValue('durationInMinutes', minutes, { shouldValidate: true })}
    />
  )
}

/**
 * The exchanges I planned in the slot being chosen. It is a warning, and planning stays possible. The check runs once
 * the slot stops changing, not for each key typed on the way.
 */
function Overlaps({ form }: { form: UseFormReturn<PlanValues> }) {
  const { t } = useTranslation()
  const [date, time, durationInMinutes] = useWatch({
    control: form.control,
    name: ['date', 'time', 'durationInMinutes'],
  })
  const slot = useMemo(() => slotOf({ date, time, durationInMinutes }), [date, time, durationInMinutes])
  const checked = useDebouncedValue(slot)
  const overlapping = useQuery({
    ...overlappingQuery(checked?.startsAt ?? '', checked?.durationInMinutes ?? 0),
    enabled: checked !== null,
  })
  const items = slot && checked ? (overlapping.data?.items ?? []) : []

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
