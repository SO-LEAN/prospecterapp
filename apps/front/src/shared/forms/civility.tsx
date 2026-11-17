import { Controller, type FieldValues } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { z } from 'zod'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/shared/ui/select'
import { FormField, type FormFieldProps } from './form-field'

export const CIVILITIES = ['madame', 'monsieur'] as const

export type Civility = (typeof CIVILITIES)[number]

/** Never required: null when not noted. */
export const civilitySchema = z.enum(CIVILITIES).nullable()

const NONE = '__none__'

type SelectProps = {
  id?: string
  value: Civility | null
  onChange: (civility: Civility | null) => void
  'aria-invalid'?: boolean
  'aria-describedby'?: string
}

function CivilitySelect({ value, onChange, ...trigger }: SelectProps) {
  const { t } = useTranslation()

  return (
    <Select
      value={value ?? NONE}
      onValueChange={(next) => onChange(next === NONE ? null : civilitySchema.parse(next))}
    >
      <SelectTrigger className="w-full" {...trigger}>
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value={NONE}>{t('civilities.none')}</SelectItem>
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
 * How one addresses a contact, in a `FormField`, never required. The `Controller` is around the field: the field
 * gives its label and its error to its direct child, the list itself.
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
