import { Link } from '@tanstack/react-router'
import { useTranslation } from 'react-i18next'
import { Badge } from '@/shared/ui/badge'
import { Card, CardContent } from '@/shared/ui/card'
import { type Contact, fullNameOf } from '../queries'

export function ContactCard({ contact }: { contact: Contact }) {
  const { t } = useTranslation()

  return (
    <Link to="/contacts/$contactId" params={{ contactId: contact.id }} className="block">
      <Card className="transition-colors hover:bg-accent">
        <CardContent className="flex items-start justify-between gap-3">
          <div className="min-w-0 space-y-1">
            <p className="truncate font-medium">{fullNameOf(contact) || t('contacts.unnamed')}</p>
            {contact.jobTitle && <p className="truncate text-muted-foreground text-sm">{contact.jobTitle}</p>}
            {contact.contactDetails.email && (
              <p className="truncate text-muted-foreground text-sm">{contact.contactDetails.email}</p>
            )}
          </div>
          {contact.masked && <Badge variant="outline">{t('contacts.masked')}</Badge>}
        </CardContent>
      </Card>
    </Link>
  )
}
