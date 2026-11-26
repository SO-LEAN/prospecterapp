import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useDebouncedValue } from '@/shared/hooks/use-debounced-value'
import { cn } from '@/shared/lib/utils'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { type OrganizationSummary, organizationsQuery } from '../queries'
import { OrganizationAvatar } from './organization-avatar'

export type ChosenHolding = { id: string; name: string; logoUrl?: string | null }

type Props = {
  value: ChosenHolding | null
  onChange: (holding: ChosenHolding | null) => void
  id?: string
  'aria-invalid'?: boolean
  'aria-describedby'?: string
}

const TILE_IN_THE_FIELD =
  'size-6 rounded-full border-0 bg-primary text-[0.625rem] text-primary-foreground [&_svg]:size-3.5'

/** A new organization's holding, searched among the account's organizations. */
export function HoldingField({ value, onChange, ...control }: Props) {
  const { t } = useTranslation()

  if (value) {
    return (
      <div className="flex h-9 items-center justify-between gap-2 rounded-md border pr-1 pl-2">
        <span className="flex min-w-0 items-center gap-2">
          <OrganizationAvatar name={value.name} logoUrl={value.logoUrl} className={TILE_IN_THE_FIELD} />
          <output className="truncate" {...control}>
            {value.name}
          </output>
        </span>
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
    <div className="relative">
      <OrganizationAvatar
        name={null}
        className={cn('pointer-events-none absolute top-1.5 left-2', TILE_IN_THE_FIELD)}
      />
      <Input
        type="search"
        className="pl-10"
        placeholder={t('organizations.holding.search')}
        value={query}
        onChange={(event) => setQuery(event.target.value)}
        {...control}
      />
      {searched !== '' && (
        <ul className="absolute inset-x-0 top-full z-10 mt-1 max-h-60 divide-y overflow-auto rounded-md border bg-popover text-popover-foreground shadow-md">
          {(candidates.data?.items ?? []).map((candidate) => (
            <li key={candidate.id}>
              <Candidate
                organization={candidate}
                onClick={() =>
                  onChoose({ id: candidate.id, name: candidate.name, logoUrl: candidate.logo?.smallUrl })
                }
              />
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}

function Candidate({ organization, onClick }: { organization: OrganizationSummary; onClick: () => void }) {
  const place = [organization.postalCode, organization.city].filter(Boolean).join(' ')

  return (
    <button
      type="button"
      className="flex w-full items-center gap-3 px-3 py-2 text-left hover:bg-accent focus-visible:bg-accent focus-visible:outline-none"
      onClick={onClick}
    >
      <span aria-hidden>
        <OrganizationAvatar
          name={organization.name}
          logoUrl={organization.logo?.smallUrl}
          className="size-8 rounded-lg text-xs"
        />
      </span>
      <span className="min-w-0">
        <span className="block truncate font-medium">{organization.name}</span>
        {place && <span className="block truncate text-muted-foreground text-xs">{place}</span>}
      </span>
    </button>
  )
}
