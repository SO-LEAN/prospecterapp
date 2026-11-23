import { Controller, type FieldValues } from 'react-hook-form'
import { CountrySelect } from '@/shared/ui/form/country-select'
import { FormField, type FormFieldProps } from './form-field'

/**
 * A form's country, in a `FormField`. The `Controller` wraps the field rather than sitting inside it. The field gives
 * its label and its error to its direct child, which must be the list itself.
 */
export function CountryField<Values extends FieldValues>({
  form,
  name,
  ...field
}: Omit<FormFieldProps<Values>, 'children'>) {
  return (
    <Controller
      control={form.control}
      name={name}
      render={({ field: { value, onChange } }) => (
        <FormField form={form} name={name} {...field}>
          <CountrySelect value={value} onChange={onChange} />
        </FormField>
      )}
    />
  )
}
