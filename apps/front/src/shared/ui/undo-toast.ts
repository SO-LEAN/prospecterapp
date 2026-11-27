import { useTranslation } from 'react-i18next'
import { toast } from 'sonner'

/** How long the message at the bottom of the screen lets the member undo the gesture it announces. */
export const UNDO_TOAST_DURATION_MS = 6000

/**
 * Tells that a gesture just succeeded, with « Annuler » when it can be undone. Escape and the cross close the message
 * without undoing anything (`Toaster`).
 */
export function useUndoToast() {
  const { t } = useTranslation()

  return (told: string, undo?: () => void) =>
    toast(told, {
      duration: UNDO_TOAST_DURATION_MS,
      action: undo && { label: t('common.undo'), onClick: undo },
    })
}
