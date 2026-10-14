import { countries } from '@/shared/lib/countries'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectSeparator,
  SelectTrigger,
  SelectValue,
} from '@/shared/ui/select'

type Props = {
  id?: string
  value: string | null | undefined
  onChange: (value: string | null) => void
  placeholder?: string
  'aria-invalid'?: boolean
  'aria-describedby'?: string
}

const NONE = '__none__'

export function CountrySelect({ value, onChange, placeholder, ...aria }: Props) {
  return (
    <Select value={value ?? NONE} onValueChange={(next) => onChange(next === NONE ? null : next)}>
      <SelectTrigger className="w-full" {...aria}>
        <SelectValue placeholder={placeholder} />
      </SelectTrigger>
      <SelectContent className="max-h-80">
        <SelectItem value={NONE}>{placeholder ?? '—'}</SelectItem>
        <SelectSeparator />
        {countries.slice(0, 8).map((country) => (
          <SelectItem key={country.code} value={country.code}>
            {country.name}
          </SelectItem>
        ))}
        <SelectSeparator />
        {countries.slice(8).map((country) => (
          <SelectItem key={country.code} value={country.code}>
            {country.name}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  )
}
