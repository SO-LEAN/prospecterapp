import { useNavigate } from '@tanstack/react-router'
import { useEffect, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import { daysBeforeErasure, urgencyOf } from '../erasure'
import { type ContactToAttach, nameOf } from '../queries'
import { AttachDialog } from './attach-dialog'

/** The browser's tabs share it. When one tab has shown a reminder, the others do not show it again. */
const STORAGE_KEY = 'prospecter.erasure-reminders'

/** When the browser keeps nothing (a private window, or its storage refused), the tab remembers on its own. */
let keptByTheTab: string[] = []

/**
 * The reminders before a contact I hold to attach is erased, read from what I have to process. One comes a week
 * before, and one comes the day before, however many tabs are open. With the right to manage personal data, which
 * attaching needs, a click opens their attachment. Without it, a click opens their page (ADR 0146).
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
    // the browser keeps nothing, so the tab remembers on its own
  }
}
