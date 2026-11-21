import { Link } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { searchOfTags } from '@/shared/api/tagged-contacts'
import { Badge } from '@/shared/ui/badge'
import { Card, CardContent } from '@/shared/ui/card'
import { CivilityMark } from '@/shared/ui/civility-mark'
import { type ContactInList, fullNameOf } from '../queries'

/**
 * A contact of the list, with links to the organizations it works for, and the tags it carries in small, each
 * opening the contacts carrying it.
 */
export function ContactCard({ contact }: { contact: ContactInList }) {
  const { t } = useTranslation()

  return (
    <Card>
      <CardContent className="space-y-2">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0 space-y-1">
            <div className="flex min-w-0 items-center gap-1.5">
              <Link
                to="/contacts/$contactId"
                params={{ contactId: contact.id }}
                className="block truncate font-medium text-primary hover:underline"
              >
                {fullNameOf(contact) || t('contacts.unnamed')}
              </Link>
              <CivilityMark civility={contact.title} />
            </div>
            {contact.jobTitle && <p className="truncate text-muted-foreground text-sm">{contact.jobTitle}</p>}
            {contact.contactDetails.email && (
              <p className="truncate text-muted-foreground text-sm">{contact.contactDetails.email}</p>
            )}
          </div>
          {contact.masked && <Badge variant="outline">{t('contacts.masked')}</Badge>}
        </div>
        <ul
          className="flex flex-wrap gap-1.5"
          aria-label={t('contacts.organizationsOf', { name: fullNameOf(contact) })}
        >
          {contact.organizations.map((organization) => (
            <li key={organization.id}>
              <Badge variant="secondary" asChild>
                <Link to="/organizations/$organizationId" params={{ organizationId: organization.id }}>
                  {organization.name}
                </Link>
              </Badge>
            </li>
          ))}
        </ul>
        {contact.tags.length > 0 && (
          <ul
            className="flex flex-wrap gap-1 text-muted-foreground text-xs"
            aria-label={t('contacts.tagsOf', { name: fullNameOf(contact) })}
          >
            {contact.tags.map((tag) => (
              <li key={tag}>
                <Link to="/contacts" search={{ tags: searchOfTags([tag]) }} className="hover:underline">
                  {tag}
                </Link>
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  )
}
