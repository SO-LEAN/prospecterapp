import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { formatMoment } from '@/shared/lib/format'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shared/ui/dialog'
import { type Chosen, type InterlocutorSearch, PlanWith } from '../../components/with-the-chosen'
import type { Slot } from '../../planning'

type Props = { slot: Slot | null; onClose: () => void; interlocutorSearch: InterlocutorSearch }

/**
 * A free calendar slot, clicked or dragged. The member searches the contact or the organization as everywhere else,
 * by the start of the name or the email. Then they plan in the slot, with its moment and duration proposed.
 */
export function PlanInASlot({ slot, onClose, interlocutorSearch }: Props) {
  const { t } = useTranslation()
  const [chosen, setChosen] = useState<Chosen | null>(null)
  const close = () => {
    setChosen(null)
    onClose()
  }

  return (
    <Dialog open={slot !== null} onOpenChange={(open) => !open && close()}>
      <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{t('exchanges.plan')}</DialogTitle>
          <DialogDescription>
            {slot && t('calendar.planIn', { moment: formatMoment(slot.startsAt.toISOString()) })}
          </DialogDescription>
        </DialogHeader>
        {slot &&
          (chosen ? (
            <PlanWith chosen={chosen} slot={slot} onCancel={() => setChosen(null)} onPlanned={close} />
          ) : (
            interlocutorSearch(setChosen)
          ))}
      </DialogContent>
    </Dialog>
  )
}
