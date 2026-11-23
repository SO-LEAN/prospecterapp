import { Controller, type FieldValues } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shared/ui/select'
import { FormField, type FormFieldProps } from './form-field'

/** Unknown (x) comes first: the choice for a contact whose title is not recorded. */
export const CIVILITIES = ['x', 'ms', 'mr'] as const

export type Civility = (typeof CIVILITIES)[number]

export const UNKNOWN_CIVILITY: Civility = 'x'

export const civilitySchema = z.enum(CIVILITIES)

type SelectProps = {
  id?: string
  value: Civility
  onChange: (civility: Civility) => void
  'aria-invalid'?: boolean
  'aria-describedby'?: string
}

function CivilitySelect({ value, onChange, ...trigger }: SelectProps) {
  const { t } = useTranslation()

  return (
    <Select value={value} onValueChange={(next) => onChange(civilitySchema.parse(next))}>
      <SelectTrigger className="w-full" {...trigger}>
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        {CIVILITIES.map((civility) => (
          <SelectItem key={civility} value={civility}>
            {t(`civilities.${civility}`)}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  )
}

/**
 * A contact's title, in a `FormField`, never required. The `Controller` wraps the field. The field gives its label
 * and its error to its direct child, the list itself.
 */
export function CivilityField<Values extends FieldValues>({
  form,
  name,
  ...field
}: Omit<FormFieldProps<Values>, 'children' | 'label'>) {
  const { t } = useTranslation()

  return (
    <Controller
      control={form.control}
      name={name}
      render={({ field: { value, onChange } }) => (
        <FormField
          form={form}
          name={name}
          label={t('fields.civility')}
          optional={t('common.optional')}
          {...field}
        >
          <CivilitySelect value={value} onChange={onChange} />
        </FormField>
      )}
    />
  )
}
