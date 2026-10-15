import { Link } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { Badge } from '@/shared/ui/badge'
import { Card, CardContent } from '@/shared/ui/card'
import { type ContactInList, fullNameOf } from '../queries'

/** A contact of the list, with links to the organizations it works for. */
export function ContactCard({ contact }: { contact: ContactInList }) {
  const { t } = useTranslation()

  return (
    <Card>
      <CardContent className="space-y-2">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0 space-y-1">
            <Link
              to="/contacts/$contactId"
              params={{ contactId: contact.id }}
              className="block truncate font-medium text-primary hover:underline"
            >
              {fullNameOf(contact) || t('contacts.unnamed')}
            </Link>
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
      </CardContent>
    </Card>
  )
}
