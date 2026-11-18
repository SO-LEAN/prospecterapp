import { useNavigate } from '@tanstack/react-router'
import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { daysBeforeErasure, urgencyOf } from '../erasure'
import { type ContactToAttach, nameOf } from '../queries'
import { AttachDialog } from './attach-dialog'

/** Shared by the tabs of the browser: a reminder one of them showed, the others do not show it again. */
const STORAGE_KEY = 'prospecter.erasure-reminders'

/** When the browser keeps nothing (a private window, its storage refused), the tab remembers on its own. */
let keptByTheTab: string[] = []

/**
 * The reminders before the erasure of a contact I hold to attach, read among what I have to process: once a week
 * before, once the day before, whatever the tabs open; a click opens their attachment with the right to manage
 * personal data, which attaching needs, their page otherwise (ADR 0146).
 */
export function ErasureReminders({ contacts }: { contacts: ContactToAttach[] }) {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [attaching, setAttaching] = useState<ContactToAttach | null>(null)

  useEffect(() => {
    const now = new Date()
    for (const contact of contacts) {
      const days = daysBeforeErasure(contact.erasableFrom, now)
      const urgency = urgencyOf(days)
      const reminder = `${contact.id}:${urgency}`
      if (urgency === 'later' || readShown().includes(reminder)) continue

      markShown(reminder)
      toast.warning(`${nameOf(contact)} — ${t('toAttach.erasedIn', { count: days })}`, {
        id: reminder,
        duration: Number.POSITIVE_INFINITY,
        action: {
          label: t('toAttach.attach'),
          onClick: () =>
            contact._links.proposedOrganization
              ? setAttaching(contact)
              : void navigate({ to: '/contacts/$contactId', params: { contactId: contact.id } }),
        },
      })
    }
  }, [contacts, navigate, t])

  return (
    <AttachDialog
      contactId={attaching?.id ?? ''}
      name={attaching ? nameOf(attaching) : ''}
      email={attaching?.email ?? null}
      open={attaching !== null}
      onClose={() => setAttaching(null)}
    />
  )
}

function readShown(): string[] {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]') as string[]
  } catch {
    return keptByTheTab
  }
}

function markShown(reminder: string) {
  keptByTheTab = [...readShown(), reminder]
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(keptByTheTab))
  } catch {
    // the browser keeps nothing: the tab remembers on its own
  }
}
