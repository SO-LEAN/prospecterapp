import { zodResolver } from '@hookform/resolvers/zod'
import { type ReactNode, useState } from 'react'
import { type DefaultValues, type FieldValues, type Path, type UseFormReturn, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import type { z } from 'zod'
import { applyViolations } from '@/shared/api/problem'
import { Alert, AlertDescription } from '@/shared/ui/alert'
import { Button } from '@/shared/ui/button'

type Props<Values extends FieldValues> = {
  schema: z.ZodType<Values, Values>
  defaultValues: DefaultValues<Values>
  fields: readonly Path<Values>[]
  save: (values: Values) => Promise<unknown>
  onDone: () => void
  children: (form: UseFormReturn<Values>) => ReactNode
}

/**
 * The small form of one intention: validated like the API, sent alone, closed when saved; the API
 * violations land on their fields, the others above the form.
 */
export function SectionForm<Values extends FieldValues>({
  schema,
  defaultValues,
  fields,
  save,
  onDone,
  children,
}: Props<Values>) {
  const { t } = useTranslation()
  const [globalErrors, setGlobalErrors] = useState<string[]>([])
  const form = useForm<Values>({ resolver: zodResolver(schema), defaultValues })
  const { isSubmitting } = form.formState

  const submit = form.handleSubmit(async (values) => {
    setGlobalErrors([])
    try {
      await save(values)
      onDone()
    } catch (error) {
      setGlobalErrors(applyViolations(error, form.setError, fields))
    }
  })

  return (
    <form onSubmit={submit} noValidate className="space-y-5">
      {globalErrors.length > 0 && (
        <Alert variant="destructive">
          <AlertDescription>{globalErrors.join(' ')}</AlertDescription>
        </Alert>
      )}
      <div className="grid gap-5 sm:grid-cols-2">{children(form)}</div>
      <div className="flex justify-end gap-2">
        <Button type="button" variant="ghost" onClick={onDone}>
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
 * A group of fields, in a form of its own values or nested in a bigger one: `path` gives the full path of
 * each of its fields (`(field) => field`, or `(field) => \`address.${field}\``).
 */
export type FieldsProps<Values extends FieldValues> = {
  form: UseFormReturn<Values>
  path?: (field: string) => Path<Values>
}

/** The fields of a group are the fields of the form. */
export const atRoot = <Values extends FieldValues>(field: string) => field as Path<Values>

/** The fields of a group are nested under `prefix` in a bigger form. */
export const under =
  <Values extends FieldValues>(prefix: string) =>
  (field: string) =>
    `${prefix}.${field}` as Path<Values>
