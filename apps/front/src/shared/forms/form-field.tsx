import type { ComponentProps } from 'react'
import { type FieldValues, get, type Path, type UseFormReturn, useFormState } from 'react-hook-form'
import { Field } from '@/shared/ui/form/field'

type Props<Values extends FieldValues> = Omit<ComponentProps<typeof Field>, 'error'> & {
  form: UseFormReturn<Values>
  name: Path<Values>
}

/**
 * A `Field` showing the error of a form field. It subscribes to that error itself: a group of fields
 * receives the same `form` on every render, so the React Compiler does not render it again on its own.
 */
export function FormField<Values extends FieldValues>({ form, name, ...field }: Props<Values>) {
  const { errors } = useFormState({ control: form.control, name })
  const error: { message?: string } | undefined = get(errors, name)

  return <Field {...field} error={error?.message} />
}
