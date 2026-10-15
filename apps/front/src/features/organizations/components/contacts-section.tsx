import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { X } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useDebouncedValue } from '@/shared/hooks/use-debounced-value'
import { Badge } from '@/shared/ui/badge'
import { Button } from '@/shared/ui/button'
import { EditableSection } from '@/shared/ui/editable-section'
import { Input } from '@/shared/ui/input'
import { useAddContact, useRemoveContact } from '../mutations'
import {
  contactCandidatesQuery,
  type Organization,
  type OrganizationContact,
  organizationContactsQuery,
} from '../queries'

const fullNameOf = (contact: Pick<OrganizationContact, 'name'>) =>
  [contact.name.firstName, contact.name.lastName].filter(Boolean).join(' ')

/** The persons prospected in the organization, read in PersonalData (ADR 0041). */
export function ContactsSection({ organization }: { organization: Organization }) {
  const { t } = useTranslation()
  const contacts = useQuery(organizationContactsQuery(organization.id))
  const remove = useRemoveContact()

  return (
    <EditableSection
      title={t('organizations.sections.contacts')}
      editable={Boolean(organization._links.addContact)}
      editor={(close) => <ContactPicker organization={organization} onDone={close} />}
    >
      {contacts.data && contacts.data.items.length === 0 && (
        <p className="text-muted-foreground">{t('organizations.contacts.none')}</p>
      )}
      <ul className="divide-y">
        {contacts.data?.items.map((contact) => (
          <li key={contact.id} className="flex items-start justify-between gap-3 py-2 first:pt-0 last:pb-0">
            <div className="min-w-0">
              <Link
                to="/contacts/$contactId"
                params={{ contactId: contact.id }}
                className="font-medium text-primary hover:underline"
              >
                {fullNameOf(contact) || t('organizations.contacts.unnamed')}
              </Link>
              {contact.jobTitle && <p className="text-muted-foreground text-sm">{contact.jobTitle}</p>}
              {contact.contactDetails.email && (
                <p className="truncate text-muted-foreground text-sm">{contact.contactDetails.email}</p>
              )}
            </div>
            <div className="flex items-center gap-2">
              {contact.masked && <Badge variant="outline">{t('organizations.contacts.masked')}</Badge>}
              {contact._links.removeFromOrganization && (
                <Button
                  variant="ghost"
                  size="sm"
                  disabled={remove.isPending}
                  aria-label={t('organizations.contacts.remove', { name: fullNameOf(contact) })}
                  onClick={() =>
                    remove.mutate({ params: { path: { id: organization.id, personId: contact.id } } })
                  }
                >
                  <X />
                </Button>
              )}
            </div>
          </li>
        ))}
      </ul>
    </EditableSection>
  )
}

function ContactPicker({ organization, onDone }: { organization: Organization; onDone: () => void }) {
  const { t } = useTranslation()
  const add = useAddContact()
  const [query, setQuery] = useState('')
  const candidates = useQuery(contactCandidatesQuery(useDebouncedValue(query)))
  const notYetAttached = (candidates.data?.items ?? []).filter(
    (candidate) => !organization.contactIds.includes(candidate.id),
  )

  const addContact = async (personId: string) => {
    await add.mutateAsync({ params: { path: { id: organization.id } }, body: { personId } })
    onDone()
  }

  return (
    <div className="space-y-3">
      <Input
        type="search"
        aria-label={t('organizations.contacts.search')}
        placeholder={t('organizations.contacts.search')}
        value={query}
        onChange={(event) => setQuery(event.target.value)}
        autoFocus
      />
      <ul className="max-h-60 divide-y overflow-auto rounded-lg border">
        {notYetAttached.map((candidate) => (
          <li key={candidate.id}>
            <button
              type="button"
              className="w-full px-3 py-2 text-left hover:bg-accent"
              disabled={add.isPending}
              onClick={() => void addContact(candidate.id)}
            >
              <span className="font-medium">
                {fullNameOf(candidate) || t('organizations.contacts.unnamed')}
              </span>
              {candidate.jobTitle && <span className="text-muted-foreground"> · {candidate.jobTitle}</span>}
            </button>
          </li>
        ))}
      </ul>
      <div className="flex items-center justify-between gap-2">
        <Button asChild variant="link" className="px-0">
          <Link to="/contacts/new">{t('organizations.contacts.collect')}</Link>
        </Button>
        <Button type="button" variant="ghost" onClick={onDone}>
          {t('common.cancel')}
        </Button>
      </div>
    </div>
  )
}
