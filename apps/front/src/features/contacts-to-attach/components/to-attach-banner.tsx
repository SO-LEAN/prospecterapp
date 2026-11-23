import { Link2, TriangleAlert } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import type { ToAttach } from '../queries'
import { AttachDialog } from './attach-dialog'

type Props = {
  contactId: string
  toAttach: ToAttach
  /** Their name and email, to attach them. They come with the right to manage personal data, which attaching needs. */
  attaching?: { name: string; email: string | null }
}

/** On the page of a contact who waits for me to attach them: the day they are erased if nobody does (ADR 0146). */
export function ToAttachBanner({ contactId, toAttach, attaching }: Props) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)
  const date = new Date(toAttach.erasableFrom).toLocaleDateString('fr-FR')

  return (
    <div
      role="status"
      className="flex flex-wrap items-center gap-3 rounded-lg border border-warning bg-warning/15 p-3 text-sm"
    >
      <TriangleAlert aria-hidden className="size-4 shrink-0" />
      <span className="flex-1 font-medium">{t('toAttach.erasedOn', { date })}</span>
      {attaching && (
        <>
          <Button size="sm" onClick={() => setOpen(true)}>
            <Link2 aria-hidden /> {t('toAttach.attach')}
          </Button>
          <AttachDialog
            contactId={contactId}
            name={attaching.name}
            email={attaching.email}
            open={open}
            onClose={() => setOpen(false)}
          />
        </>
      )}
    </div>
  )
}
