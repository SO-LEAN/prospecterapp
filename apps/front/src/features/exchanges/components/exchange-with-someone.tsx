import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shared/ui/dialog'
import { type Chosen, type InterlocutorSearch, NoteWith, PlanWith } from './with-the-chosen'

/** What the member does with whoever they choose. */
export type Gesture = 'note' | 'plan'

const TITLES = { note: 'exchanges.note', plan: 'exchanges.planAnExchange' } as const

type Props = { gesture: Gesture | null; onClose: () => void; interlocutorSearch: InterlocutorSearch }

/** An exchange noted or planned away from any card: the member first chooses who it is with, by the search. */
export function ExchangeWithSomeone({ gesture, onClose, interlocutorSearch }: Props) {
  const { t } = useTranslation()
  const [chosen, setChosen] = useState<Chosen | null>(null)
  const close = () => {
    setChosen(null)
    onClose()
  }

  return (
    <Dialog open={gesture !== null} onOpenChange={(open) => !open && close()}>
      <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{gesture && t(TITLES[gesture])}</DialogTitle>
          <DialogDescription>{t('exchanges.withSomeone')}</DialogDescription>
        </DialogHeader>
        {chosen === null && interlocutorSearch(setChosen)}
        {chosen && gesture === 'note' && (
          <NoteWith chosen={chosen} onCancel={() => setChosen(null)} onNoted={close} />
        )}
        {chosen && gesture === 'plan' && (
          <PlanWith chosen={chosen} onCancel={() => setChosen(null)} onPlanned={close} />
        )}
      </DialogContent>
    </Dialog>
  )
}
