import { zodResolver } from '@hookform/resolvers/zod'
import { Building2, UserRound } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useForm, useFormState } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { applyViolations } from '@/shared/api/problem'
import { Choice, Choices } from '@/shared/forms/choices'
import { FormField } from '@/shared/forms/form-field'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { DUE_SHORTCUTS, type DueShortcut } from '../due'
import type { ToDoInput } from '../queries'
import { contactIdOf, ITSELF, newToDo, type ToDoValues, toDoFields, toDoSchema, toToDoInput } from '../schema'

/** Who a to-do can be for on an organization's card: the organization itself, first and by default, or one of its contacts. */
export type Recipients = { organization: string; contacts: { id: string; name: string }[] }

/** Gets the to-do, and the contact chosen, null for the organization itself or without a choice. */
type Submit = (toDo: ToDoInput, contactId: string | null) => Promise<unknown>

type Props = {
  onSubmit: Submit
  submitLabel: string
  onCancel: () => void
  /** The to-do to revise. Without it, a new to-do is due tomorrow. */
  defaultValues?: ToDoValues
  /** Logs the new to-do and readies the form for another one. The form offers it beside the submit button. */
  onSubmitThenAnother?: Submit
  /** Without them, or without a contact among them, the form offers no choice of whom the to-do is for. */
  recipients?: Recipients
}

/** What there is to do, then its day by a shortcut, and an hour when it matters. The text has the focus once the form opens. */
export function ToDoForm({
  onSubmit,
  submitLabel,
  onCancel,
  defaultValues,
  onSubmitThenAnother,
  recipients,
}: Props) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<ToDoValues>({
    resolver: zodResolver(toDoSchema),
    defaultValues: defaultValues ?? newToDo(new Date()),
  })
  const { isSubmitting } = useFormState({ control: form.control })

  useEffect(() => {
    form.setFocus('text')
  }, [form])

  /** Once the day is chosen, the text takes the focus back. So Enter adds the to-do rather than presses the shortcut again. */
  const dueOnTheDayOf = (shortcut: DueShortcut) => {
    form.setValue('dueOn', DUE_SHORTCUTS[shortcut](new Date()), { shouldValidate: true })
    form.setFocus('text')
  }

  const submitBy = (send: Submit) =>
    form.handleSubmit(async (values) => {
      setGlobalErrors([])
      try {
        await send(toToDoInput(values), contactIdOf(values))
      } catch (error) {
        setGlobalErrors(applyViolations(error, form.setError, toDoFields))
      }
    })

  return (
    <form onSubmit={submitBy(onSubmit)} noValidate className="space-y-2">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}
      {recipients && recipients.contacts.length > 0 && (
        <Choices legend={t('toDos.for')}>
          <Choice
            icon={Building2}
            label={recipients.organization}
            value={ITSELF}
            {...form.register('forContact')}
          />
          {recipients.contacts.map((contact) => (
            <Choice
              key={contact.id}
              icon={UserRound}
              label={contact.name}
              value={contact.id}
              {...form.register('forContact')}
            />
          ))}
        </Choices>
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
      <fieldset className="flex flex-wrap gap-1">
        <legend className="sr-only">{t('toDos.shortcuts')}</legend>
        {(Object.keys(DUE_SHORTCUTS) as DueShortcut[]).map((shortcut) => (
          <Button
            key={shortcut}
            type="button"
            size="sm"
            variant="ghost"
            onClick={() => dueOnTheDayOf(shortcut)}
          >
            {t(`toDos.shortcut.${shortcut}`)}
          </Button>
        ))}
      </fieldset>
      <div className="flex flex-wrap items-center gap-2">
        {onSubmitThenAnother && (
          <Button
            type="button"
            variant="link"
            className="px-0"
            disabled={isSubmitting}
            onClick={submitBy(onSubmitThenAnother)}
          >
            {t('toDos.addAnother')}
          </Button>
        )}
        <div className="ml-auto flex gap-2">
          <Button type="button" variant="ghost" onClick={onCancel}>
            {t('common.cancel')}
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting ? t('common.saving') : submitLabel}
          </Button>
        </div>
      </div>
    </form>
  )
}
