import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm, useFormState } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import type { ExchangeDetail, VenueCorrection } from '../queries'
import { type VenueValues, venueFieldNames, venueOf, venueSchema } from '../venue'
import { VideoCallLinkInput } from './video-call-link-input'

type Props = {
  exchange: Pick<ExchangeDetail, 'channel' | 'place' | 'link'>
  onSubmit: (venue: VenueCorrection) => Promise<unknown>
  onCancel: () => void
}

/** The place of a meeting, or the link of a video call, the first Meet, Teams or Zoom link of an invitation pasted. */
export function VenueCorrectionForm({ exchange, onSubmit, onCancel }: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<VenueValues>({
    resolver: zodResolver(venueSchema),
    defaultValues: { channel: exchange.channel, place: exchange.place ?? '', link: exchange.link ?? '' },
  })
  const { isSubmitting } = useFormState({ control: form.control })

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await onSubmit(venueOf(values))
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, venueFieldNames))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-4">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}
      {exchange.channel === 'meeting' ? (
        <FormField form={form} name="place" label={t('exchanges.place')}>
          <Input autoComplete="off" {...form.register('place')} />
        </FormField>
      ) : (
        <FormField form={form} name="link" label={t('exchanges.link')} hint={t('exchanges.linkHint')}>
          <VideoCallLinkInput
            {...form.register('link')}
            onLink={(link) => form.setValue('link', link, { shouldValidate: true })}
          />
        </FormField>
      )}
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
