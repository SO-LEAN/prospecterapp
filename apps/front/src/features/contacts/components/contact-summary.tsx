import { useQuery } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { Mail, Phone } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { initials, telUrl } from '@/shared/lib/format'
import { Avatar, AvatarFallback } from '@/shared/ui/avatar'
import { Badge } from '@/shared/ui/badge'
import { Skeleton } from '@/shared/ui/skeleton'
import { contactQuery, fullNameOf, organizationsOfContactQuery } from '../queries'

/**
 * A short summary of a contact, beside one of their exchanges. It shows who they are, what they do and where. One
 * click calls them or writes to them. A contact has no picture, so their initials stand in for it. A member without the
 * right to personal data reads only their name and organizations. The rest comes masked.
 */
export function ContactSummary({ contactId }: { contactId: string }) {
  const { t } = useTranslation()
  const contact = useQuery(contactQuery(contactId))
  const organizations = useQuery(organizationsOfContactQuery(contactId))

  if (!contact.data) return <Skeleton className="h-14 w-full" />

  const name = fullNameOf(contact.data) || t('contacts.unnamed')
  const { masked } = contact.data
  const email = masked ? null : contact.data.contactDetails.email
  const phoneNumber = masked ? null : contact.data.contactDetails.phoneNumber

  return (
    <div className="space-y-2 text-sm">
      <div className="flex items-center gap-3">
        <Avatar size="lg">
          <AvatarFallback className="bg-primary/10 font-medium text-primary">{initials(name)}</AvatarFallback>
        </Avatar>
        <div className="min-w-0">
          <p className="truncate font-medium">{name}</p>
          {!masked && contact.data.jobTitle && (
            <p className="truncate text-muted-foreground">{contact.data.jobTitle}</p>
          )}
        </div>
      </div>
      {(organizations.data?.items.length ?? 0) > 0 && (
        <ul className="flex flex-wrap gap-1.5" aria-label={t('contacts.organizationsOf', { name })}>
          {organizations.data?.items.map((organization) => (
            <li key={organization.id}>
              <Badge variant="secondary" asChild>
                <Link to="/organizations/$organizationId" params={{ organizationId: organization.id }}>
                  {organization.name}
                </Link>
              </Badge>
            </li>
          ))}
        </ul>
      )}
      <div className="flex flex-wrap gap-x-4 gap-y-1">
        {phoneNumber && (
          <a href={telUrl(phoneNumber)} className="flex items-center gap-1.5 text-primary hover:underline">
            <Phone aria-hidden className="size-4" /> {phoneNumber}
          </a>
        )}
        {email && (
          <a
            href={`mailto:${email}`}
            className="flex min-w-0 items-center gap-1.5 text-primary hover:underline"
          >
            <Mail aria-hidden className="size-4 shrink-0" /> <span className="truncate">{email}</span>
          </a>
        )}
      </div>
    </div>
  )
}
