import { zodResolver } from '@hookform/resolvers/zod'
import { type ChangeEvent, useState } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations, isProblem } from '@/shared/api/problem'
import type { components } from '@/shared/api/schema'
import { FormField } from '@/shared/forms/form-field'
import { phoneAsTyped } from '@/shared/forms/phone'
import { orNull } from '@/shared/forms/text'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { Textarea } from '@/shared/ui/textarea'
import { type BookingValues, bookingFields, bookingSchema, noBooking } from '../booking'
import { useBookSlot } from '../mutations'

type Channel = components['schemas']['BookingInput']['channel']
type Violation = components['schemas']['ViolationResource']

/** How a booking ended: booked, or refused for what the page tells in place of its form. */
export type BookingOutcome =
  | { kind: 'booked'; email: string; phone: string }
  | { kind: 'alreadyBooked'; appointment: string }
  | { kind: 'slotTaken' }

type Props = {
  code: string
  channel: NonNullable<Channel>
  slot: string
  recap: string
  onDone: (outcome: BookingOutcome) => void
}

const refusalOf = (error: unknown): Violation | undefined =>
  isProblem(error)
    ? error.violations?.find(({ reason }) => reason === 'alreadyBooked' || reason === 'slotNotFree')
    : undefined

/**
 * Who books the slot chosen (ADR 0164): the trap no person fills is hidden from them and from the screen readers, and
 * the page says when it showed the form, for the API to leave aside a form filled by a robot.
 */
export function BookingForm({ code, channel, slot, recap, onDone }: Props) {
  const { t } = useTranslation()
  const [formShownAt] = useState(() => new Date().toISOString())
  const [unplaced, setUnplaced] = useState<string[]>([])
  const form = useForm<BookingValues>({ resolver: zodResolver(bookingSchema), defaultValues: noBooking })
  const book = useBookSlot()

  const submit = form.handleSubmit((values) =>
    book.mutate(
      {
        params: { path: { code } },
        body: { slot, channel, ...values, subject: orNull(values.subject), formShownAt },
      },
      {
        onSuccess: () => onDone({ kind: 'booked', email: values.email, phone: values.phone }),
        onError: (error) => {
          const refusal = refusalOf(error)
          if (refusal?.reason === 'alreadyBooked') {
            onDone({ kind: 'alreadyBooked', appointment: String(refusal.context?.appointment ?? slot) })
          } else if (refusal) {
            onDone({ kind: 'slotTaken' })
          } else {
            setUnplaced(applyViolations(error, form.setError, bookingFields, t('booking.public.form.failed')))
          }
        },
      },
    ),
  )

  return (
    <form onSubmit={submit} className="grid max-w-xl gap-4" noValidate>
      <h2 className="font-semibold text-lg">{t('booking.public.form.title')}</h2>
      <p className="font-medium text-sm">{recap}</p>
      <div className="grid gap-4 sm:grid-cols-2">
        <FormField form={form} name="firstName" label={t('booking.public.form.firstName')}>
          <Input autoComplete="given-name" {...form.register('firstName')} />
        </FormField>
        <FormField form={form} name="lastName" label={t('booking.public.form.lastName')}>
          <Input autoComplete="family-name" {...form.register('lastName')} />
        </FormField>
        <FormField
          form={form}
          name="phone"
          label={t('booking.public.form.phone')}
          hint={t('booking.public.form.phoneHint')}
        >
          <Input
            type="tel"
            autoComplete="tel"
            {...form.register('phone', {
              onChange: (event: ChangeEvent<HTMLInputElement>) =>
                form.setValue('phone', phoneAsTyped(event.target.value)),
            })}
          />
        </FormField>
        <FormField form={form} name="email" label={t('booking.public.form.email')}>
          <Input type="email" autoComplete="email" {...form.register('email')} />
        </FormField>
      </div>
      <FormField
        form={form}
        name="subject"
        label={t('booking.public.form.subject')}
        optional={t('booking.public.form.optional')}
      >
        <Textarea rows={3} {...form.register('subject')} />
      </FormField>
      <div aria-hidden="true" className="hidden">
        <label>
          {t('booking.public.form.trap')}
          <input tabIndex={-1} autoComplete="off" {...form.register('website')} />
        </label>
      </div>
      <p className="text-muted-foreground text-xs">{t('booking.public.form.privacy')}</p>
      {unplaced.map((message) => (
        <p key={message} role="alert" className="text-destructive text-sm">
          {message}
        </p>
      ))}
      <Button type="submit" disabled={book.isPending} className="justify-self-start">
        {t('booking.public.form.submit')}
      </Button>
    </form>
  )
}
