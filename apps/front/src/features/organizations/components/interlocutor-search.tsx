import { useQuery } from '@tanstack/react-query'
import { Building2, Search, User } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useDebouncedValue } from '@/shared/hooks/use-debounced-value'
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { contactCandidatesQuery, fullNameOf, organizationsQuery } from '../queries'

/** Who an exchange is with: a contact, or the organization itself. */
export type Interlocutor = { kind: 'person' | 'organization'; id: string; name: string }

/**
 * A contact or an organization searched as everywhere, by the start of their name or of their email, the contacts
 * first; one chosen in a click.
 */
export function InterlocutorSearch({ onChoose }: { onChoose: (interlocutor: Interlocutor) => void }) {
  const { t } = useTranslation()
  const [text, setText] = useState('')
  const q = useDebouncedValue(text.trim())
  const contacts = useQuery({ ...contactCandidatesQuery(q), enabled: q !== '' })
  const organizations = useQuery({ ...organizationsQuery(q), enabled: q !== '' })
  const found: Interlocutor[] = [
    ...(contacts.data?.items ?? []).map((contact) => ({
      kind: 'person' as const,
      id: contact.id,
      name: fullNameOf(contact) || t('organizations.contacts.unnamed'),
    })),
    ...(organizations.data?.items ?? []).map((organization) => ({
      kind: 'organization' as const,
      id: organization.id,
      name: organization.name,
    })),
  ]

  return (
    <div className="space-y-3">
      <div className="relative">
        <Search aria-hidden className="absolute top-2.5 left-2.5 size-4 text-muted-foreground" />
        <Input
          type="search"
          autoFocus
          className="pl-8"
          aria-label={t('organizations.interlocutorSearch.label')}
          placeholder={t('organizations.interlocutorSearch.label')}
          value={text}
          onChange={(event) => setText(event.target.value)}
        />
      </div>
      {q !== '' && contacts.isSuccess && organizations.isSuccess && found.length === 0 && (
        <p className="text-muted-foreground text-sm">{t('organizations.interlocutorSearch.nobody')}</p>
      )}
      <ul className="space-y-1" aria-label={t('organizations.interlocutorSearch.found')}>
        {found.map((interlocutor) => {
          const Icon = interlocutor.kind === 'person' ? User : Building2
          return (
            <li key={`${interlocutor.kind}-${interlocutor.id}`}>
              <Button variant="ghost" className="w-full justify-start" onClick={() => onChoose(interlocutor)}>
                <Icon aria-hidden /> {interlocutor.name}
              </Button>
            </li>
          )
        })}
      </ul>
    </div>
  )
}
