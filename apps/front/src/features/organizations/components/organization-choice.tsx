import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { Building2, Search } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useDebouncedValue } from '@/shared/hooks/use-debounced-value'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { organizationsQuery } from '../queries'

type Chosen = { id: string; name: string }

/** What the field of the choice gives its control: the id its label names, and its error. */
type FieldWiring = { id?: string; 'aria-invalid'?: boolean; 'aria-describedby'?: string }

type Props = FieldWiring & {
  onChoose: (organizationId: string) => void
}

/**
 * Chooses one of the organizations the account prospects, found by the start of their name. Its field labels its
 * search and shows its error.
 */
export function OrganizationChoice({ onChoose, ...wiring }: Props) {
  const [chosen, setChosen] = useState<Chosen | null>(null)

  const choose = (organization: Chosen) => {
    setChosen(organization)
    onChoose(organization.id)
  }
  const chooseAnother = () => {
    setChosen(null)
    onChoose('')
  }

  return chosen ? (
    <ChosenOrganization organization={chosen} onChange={chooseAnother} />
  ) : (
    <OrganizationSearch onChoose={choose} {...wiring} />
  )
}

function ChosenOrganization({ organization, onChange }: { organization: Chosen; onChange: () => void }) {
  const { t } = useTranslation()

  return (
    <div className="flex items-center justify-between gap-2 rounded-lg border px-3 py-1.5">
      <span className="flex items-center gap-2 font-medium">
        <Building2 aria-hidden className="size-4 text-muted-foreground" /> {organization.name}
      </span>
      <Button type="button" variant="ghost" size="sm" onClick={onChange}>
        {t('organizations.choice.change')}
      </Button>
    </div>
  )
}

function OrganizationSearch({
  onChoose,
  ...wiring
}: FieldWiring & { onChoose: (organization: Chosen) => void }) {
  const { t } = useTranslation()
  const [text, setText] = useState('')
  const q = useDebouncedValue(text.trim())
  const organizations = useQuery({ ...organizationsQuery(q), enabled: q !== '' })
  const found = organizations.data?.items ?? []

  return (
    <div className="space-y-2">
      <div className="relative">
        <Search aria-hidden className="absolute top-2.5 left-2.5 size-4 text-muted-foreground" />
        <Input
          type="search"
          className="pl-8"
          {...wiring}
          placeholder={t('organizations.choice.search')}
          value={text}
          onChange={(event) => setText(event.target.value)}
        />
      </div>
      {organizations.isSuccess && found.length === 0 && <NoOrganization q={q} />}
      <ul className="space-y-1" aria-label={t('organizations.choice.found')}>
        {found.map((organization) => (
          <li key={organization.id}>
            <Button
              type="button"
              variant="ghost"
              className="w-full justify-start"
              onClick={() => onChoose(organization)}
            >
              <Building2 aria-hidden /> {organization.name}
            </Button>
          </li>
        ))}
      </ul>
    </div>
  )
}

function NoOrganization({ q }: { q: string }) {
  const { t } = useTranslation()

  return (
    <p className="text-muted-foreground text-sm">
      {t('organizations.choice.none', { q })}{' '}
      <Link to="/organizations/new" className="font-medium text-primary hover:underline">
        {t('organizations.choice.start')}
      </Link>
    </p>
  )
}
