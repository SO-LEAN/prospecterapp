import { Link } from '@tanstack/react-router'
import { Link2, UserRound } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { formatMoment } from '@/shared/lib/format'
import { cn } from '@/shared/lib/utils'
import { Button } from '@/shared/ui/button'
import { daysBeforeErasure, type Urgency, urgencyOf } from '../erasure'
import { type ContactToAttach, nameOf } from '../queries'
import { AttachDialog } from './attach-dialog'

const URGENCY_LOOKS: Record<Urgency, string> = {
  later: 'text-muted-foreground',
  soon: 'text-warning-foreground bg-warning/20',
  imminent: 'text-destructive bg-destructive/10',
}

/**
 * The contacts who wait for me to attach them, among what I have to process. The first to be erased comes first.
 * Each shows its name, its email, its last meeting and the days left before its erasure. The days turn orange a
 * week before and red the day before. With the right to manage personal data, I attach them from here (ADR 0146).
 * On a phone, the days sit above the attachment, so that the name and the date keep a line each.
 */
export function ContactsToAttachSection({ contacts }: { contacts: ContactToAttach[] }) {
  const { t } = useTranslation()
  const [attaching, setAttaching] = useState<ContactToAttach | null>(null)

  if (contacts.length === 0) return null
  return (
    <section aria-label={t('toAttach.title')} className="mt-3 space-y-2">
      <h3 className="font-medium text-muted-foreground text-sm">{t('toAttach.title')}</h3>
      <ul className="space-y-2">
        {contacts.map((contact) => (
          <ContactToAttachLine key={contact.id} contact={contact} onAttach={() => setAttaching(contact)} />
        ))}
      </ul>
      <AttachDialog
        contactId={attaching?.id ?? ''}
        name={attaching ? nameOf(attaching) : ''}
        email={attaching?.email ?? null}
        open={attaching !== null}
        onClose={() => setAttaching(null)}
      />
    </section>
  )
}

function ContactToAttachLine({ contact, onAttach }: { contact: ContactToAttach; onAttach: () => void }) {
  const { t } = useTranslation()
  const days = daysBeforeErasure(contact.erasableFrom, new Date())
  const urgency = urgencyOf(days)

  return (
    <li className="flex items-center gap-3 rounded-lg border p-3">
      <span
        aria-hidden
        className="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"
      >
        <UserRound className="size-4" />
      </span>
      <div className="min-w-0 flex-1">
        <Link
          to="/contacts/$contactId"
          params={{ contactId: contact.id }}
          className="block truncate font-medium hover:underline"
        >
          {nameOf(contact)}
        </Link>
        {contact.email && <p className="truncate text-muted-foreground text-sm">{contact.email}</p>}
        <p className="whitespace-nowrap text-muted-foreground text-xs">
          {formatMoment(contact.lastMeetingAt)}
        </p>
      </div>
      <div className="flex shrink-0 flex-col items-end gap-1 sm:flex-row sm:items-center sm:gap-3">
        <span
          data-urgency={urgency}
          title={t('toAttach.erasedIn', { count: days })}
          className={cn('rounded-md px-1.5 py-0.5 font-medium text-xs', URGENCY_LOOKS[urgency])}
        >
          <span aria-hidden>{t('toAttach.daysLeft', { count: days })}</span>
          <span className="sr-only">{t('toAttach.erasedIn', { count: days })}</span>
        </span>
        {contact._links.proposedOrganization && (
          <Button variant="outline" size="sm" className="h-11 sm:h-8" onClick={onAttach}>
            <Link2 aria-hidden className="max-sm:hidden" /> {t('toAttach.attach')}
          </Button>
        )}
      </div>
    </li>
  )
}
