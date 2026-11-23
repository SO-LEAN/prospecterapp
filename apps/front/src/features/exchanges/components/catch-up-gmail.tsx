import { Loader2, MailSearch } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { type CatchUpCorrespondent, useCatchUpGmail } from '../mutations'

/**
 * Catches up the e-mails that my Gmail mailbox exchanged with a contact, or with an organization itself. It takes
 * the 200 newest ones from the last 12 months. They arrive in the timeline as the import logs them. The button shows
 * when the timeline gives its link.
 */
export function CatchUpGmail(correspondent: CatchUpCorrespondent) {
  const { t } = useTranslation()
  const catchUp = useCatchUpGmail()

  return (
    <Button
      size="sm"
      variant="outline"
      disabled={catchUp.isPending}
      onClick={() => catchUp.mutate(correspondent)}
    >
      {catchUp.isPending ? <Loader2 aria-hidden className="animate-spin" /> : <MailSearch aria-hidden />}
      {t(catchUp.isPending ? 'exchanges.catchUpGmail.reading' : 'exchanges.catchUpGmail.action')}
    </Button>
  )
}
