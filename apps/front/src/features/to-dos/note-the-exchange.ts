import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import type { ToDo } from './queries'

/**
 * Once a to-do is ticked off, « Noter l'échange ? » is proposed, the email sent for instance, so that the timeline
 * stays complete; ignored, nothing happens. Accepted, the page opens the quick entry of an exchange with the to-do
 * it was proposed for.
 */
export function useNoteTheExchangeProposal() {
  const { t } = useTranslation()
  const [accepted, setAccepted] = useState<ToDo | null>(null)

  const propose = (toDo: ToDo) => {
    toast(t('toDos.noteTheExchange'), {
      description: toDo.text,
      action: { label: t('toDos.noteTheExchangeAction'), onClick: () => setAccepted(toDo) },
    })
  }

  return { accepted, propose, close: () => setAccepted(null) }
}
