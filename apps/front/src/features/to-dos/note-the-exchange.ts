import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'
import type { ToDo } from './queries'

/**
 * Once a to-do is ticked off, the page proposes « Noter l'échange ? », for instance the email sent. So the timeline
 * stays complete. When the member ignores it, nothing happens. When they accept it, the page opens an exchange's quick
 * entry with the to-do it was proposed for.
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
