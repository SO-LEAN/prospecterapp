import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useDebouncedValue } from '@/shared/hooks/use-debounced-value'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { organizationsQuery } from '../queries'

export type ChosenHolding = { id: string; name: string }

type Props = {
  value: ChosenHolding | null
  onChange: (holding: ChosenHolding | null) => void
  id?: string
  'aria-invalid'?: boolean
  'aria-describedby'?: string
}

/** A new organization's holding, searched among the account's organizations. */
export function HoldingField({ value, onChange, ...control }: Props) {
  const { t } = useTranslation()

  if (value) {
    return (
      <div className="flex items-center justify-between gap-3 rounded-lg border px-3 py-2">
        <output {...control}>{value.name}</output>
        <Button type="button" variant="ghost" size="sm" onClick={() => onChange(null)}>
          {t('organizations.holding.remove')}
        </Button>
      </div>
    )
  }

  return <HoldingSearch onChoose={onChange} control={control} />
}

function HoldingSearch({
  onChoose,
  control,
}: {
  onChoose: (holding: ChosenHolding) => void
  control: Omit<Props, 'value' | 'onChange'>
}) {
  const { t } = useTranslation()
  const [query, setQuery] = useState('')
  const searched = useDebouncedValue(query)
  const candidates = useQuery({ ...organizationsQuery(searched), enabled: searched !== '' })

  return (
    <div className="space-y-2">
      <Input
        type="search"
        placeholder={t('organizations.holding.search')}
        value={query}
        onChange={(event) => setQuery(event.target.value)}
        {...control}
      />
      {searched !== '' && (
        <ul className="max-h-60 divide-y overflow-auto rounded-lg border">
          {(candidates.data?.items ?? []).map((candidate) => (
            <li key={candidate.id}>
              <button
                type="button"
                className="w-full px-3 py-2 text-left hover:bg-accent"
                onClick={() => onChoose({ id: candidate.id, name: candidate.name })}
              >
                {candidate.name}
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
