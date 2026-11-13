import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm, useFormState } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { FormField } from '@/shared/forms/form-field'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { DUE_SHORTCUTS, type DueShortcut } from '../due'
import type { ToDoInput } from '../queries'
import { newToDo, type ToDoValues, toDoFields, toDoSchema, toToDoInput } from '../schema'

type Props = {
  onSubmit: (toDo: ToDoInput) => Promise<unknown>
  /** The to-do to revise; a new one is due tomorrow by default. */
  defaultValues?: ToDoValues
  /** A revision is cancelled; the quick entry has no cancel. */
  onCancel?: () => void
}

/** Noted in one line: what is to do, then its day, by a shortcut, and an hour when it matters. */
export function ToDoForm({ onSubmit, defaultValues, onCancel }: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<ToDoValues>({
    resolver: zodResolver(toDoSchema),
    defaultValues: defaultValues ?? newToDo(new Date()),
  })
  const { isSubmitting } = useFormState({ control: form.control })

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await onSubmit(toToDoInput(values))
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, toDoFields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-2">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}
      <div className="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
        <FormField form={form} name="text" label={t('toDos.text')}>
          <Input placeholder={t('toDos.textPlaceholder')} {...form.register('text')} />
        </FormField>
        <FormField form={form} name="dueOn" label={t('toDos.dueOn')}>
          <Input type="date" {...form.register('dueOn')} />
        </FormField>
        <FormField form={form} name="dueAt" label={t('toDos.dueAt')} optional={t('common.optional')}>
          <Input type="time" {...form.register('dueAt')} />
        </FormField>
      </div>
      <div className="flex flex-wrap items-center justify-between gap-2">
        <fieldset className="flex flex-wrap gap-1">
          <legend className="sr-only">{t('toDos.shortcuts')}</legend>
          {(Object.keys(DUE_SHORTCUTS) as DueShortcut[]).map((shortcut) => (
            <Button
              key={shortcut}
              type="button"
              size="sm"
              variant="ghost"
              onClick={() =>
                form.setValue('dueOn', DUE_SHORTCUTS[shortcut](new Date()), { shouldValidate: true })
              }
            >
              {t(`toDos.shortcut.${shortcut}`)}
            </Button>
          ))}
        </fieldset>
        <div className="flex gap-2">
          {onCancel && (
            <Button type="button" variant="ghost" onClick={onCancel}>
              {t('common.cancel')}
            </Button>
          )}
          <Button type="submit" size="sm" disabled={isSubmitting}>
            {isSubmitting ? t('common.saving') : onCancel ? t('common.save') : t('toDos.add')}
          </Button>
        </div>
      </div>
    </form>
  )
}
