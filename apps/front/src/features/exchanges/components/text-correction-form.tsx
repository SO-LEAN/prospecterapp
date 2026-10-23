import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm, useFormState } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Textarea } from '@/shared/ui/textarea'
import type { TextCorrection } from '../queries'
import { type TextValues, textFields, textSchema, toTextCorrection } from '../schema'
import { MadeConformField } from './made-conform-field'

type Props = {
  text: string | null | undefined
  /** Its contact forgotten, the correction may declare it made conform. */
  mayDeclareConform: boolean
  onSubmit: (correction: TextCorrection) => Promise<unknown>
  onCancel: () => void
}

/** The text of an exchange alone, to make it conform; its other facts stay. */
export function TextCorrectionForm({ text, mayDeclareConform, onSubmit, onCancel }: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<TextValues>({
    resolver: zodResolver(textSchema),
    defaultValues: { text: text ?? '', madeConform: false },
  })
  const { isSubmitting } = useFormState({ control: form.control })

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await onSubmit(toTextCorrection(values))
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, textFields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-4">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}
      <FormField form={form} name="text" label={t('exchanges.text')} optional={t('common.optional')}>
        <Textarea rows={5} {...form.register('text')} />
      </FormField>
      {mayDeclareConform && <MadeConformField {...form.register('madeConform')} />}
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
