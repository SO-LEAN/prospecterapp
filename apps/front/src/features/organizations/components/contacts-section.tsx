import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { X } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useDebouncedValue } from '@/shared/hooks/use-debounced-value'
import { Badge } from '@/shared/ui/badge'
import { Button } from '@/shared/ui/button'
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/shared/ui/dialog'
import { EditableSection } from '@/shared/ui/editable-section'
import { Input } from '@/shared/ui/input'
import { useAddContact, useCollectContact, useRemoveContact } from '../mutations'
import {
  contactCandidatesQuery,
  type Organization,
  type OrganizationContact,
  organizationContactsQuery,
} from '../queries'
import { CollectContactForm } from './collect-contact-form'

const fullNameOf = (contact: Pick<OrganizationContact, 'name'>) =>
  [contact.name.firstName, contact.name.lastName].filter(Boolean).join(' ')

/** The persons prospected in the organization, read in PersonalData (ADR 0041). */
export function ContactsSection({ organization }: { organization: Organization }) {
  const { t } = useTranslation()
  const contacts = useQuery(organizationContactsQuery(organization.id))

  return (
    <EditableSection
      title={t('organizations.sections.contacts')}
      editable={Boolean(organization._links.addContact)}
      editor={(close) => <ContactAdder organization={organization} onDone={close} />}
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
                <RemoveContact organization={organization} contact={contact} />
              )}
            </div>
          </li>
        ))}
      </ul>
    </EditableSection>
  )
}

type Adding = 'new' | 'existing'

/** A new contact of the organization, or one of my contacts already working elsewhere. */
function ContactAdder({ organization, onDone }: { organization: Organization; onDone: () => void }) {
  const { t } = useTranslation()
  const [adding, setAdding] = useState<Adding>('new')
  const collect = useCollectContact()

  return (
    <div className="space-y-4">
      <fieldset className="flex gap-2">
        <legend className="sr-only">{t('organizations.contacts.add')}</legend>
        <Button
          type="button"
          size="sm"
          variant={adding === 'new' ? 'secondary' : 'ghost'}
          aria-pressed={adding === 'new'}
          onClick={() => setAdding('new')}
        >
          {t('organizations.contacts.addNew')}
        </Button>
        <Button
          type="button"
          size="sm"
          variant={adding === 'existing' ? 'secondary' : 'ghost'}
          aria-pressed={adding === 'existing'}
          onClick={() => setAdding('existing')}
        >
          {t('organizations.contacts.addExisting')}
        </Button>
      </fieldset>
      {adding === 'new' ? (
        <CollectContactForm
          organizationId={organization.id}
          onCancel={onDone}
          onSubmit={async (body) => {
            await collect.mutateAsync({ body })
            onDone()
          }}
        />
      ) : (
        <ContactPicker organization={organization} onDone={onDone} />
      )}
    </div>
  )
}

/** Removing a contact from its last organization erases it: it is confirmed first. */
function RemoveContact({
  organization,
  contact,
}: {
  organization: Organization
  contact: OrganizationContact
}) {
  const { t } = useTranslation()
  const remove = useRemoveContact()
  const [open, setOpen] = useState(false)
  const name = fullNameOf(contact) || t('organizations.contacts.unnamed')

  const confirm = async () => {
    await remove.mutateAsync({ params: { path: { id: organization.id, personId: contact.id } } })
    setOpen(false)
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="ghost" size="sm" aria-label={t('organizations.contacts.remove', { name })}>
          <X />
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('organizations.contacts.removeTitle', { name })}</DialogTitle>
          <DialogDescription>{t('organizations.contacts.removeDescription')}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="ghost">{t('common.cancel')}</Button>
          </DialogClose>
          <Button variant="destructive" disabled={remove.isPending} onClick={() => void confirm()}>
            {t('organizations.contacts.removeConfirm')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
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
      <div className="flex justify-end">
        <Button type="button" variant="ghost" onClick={onDone}>
          {t('common.cancel')}
        </Button>
      </div>
    </div>
  )
}
