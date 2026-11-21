import { Loader2, MailSearch } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/shared/ui/button'
import { type CatchUpCorrespondent, useCatchUpGmail } from '../mutations'

/**
 * Catches up the e-mails my mailbox of Gmail exchanged with a contact, or with an organization itself, the 200 newest
 * of the 12 months before: they arrive in the timeline as the import notes them. Shown when the timeline links it.
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
