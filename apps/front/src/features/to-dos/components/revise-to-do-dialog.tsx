import { useTranslation } from 'react-i18next'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/shared/ui/dialog'
import { useReviseToDo } from '../mutations'
import type { ToDo } from '../queries'
import { revisionOf } from '../schema'
import { ToDoForm } from './to-do-form'

type Props = { toDo: ToDo; open: boolean; onOpenChange: (open: boolean) => void }

/** Revises a to-do's text, its day and its hour. Its shortcuts set a new day, which postpones it. */
export function ReviseToDoDialog({ toDo, open, onOpenChange }: Props) {
  const { t } = useTranslation()
  const revise = useReviseToDo()

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t('toDos.revise')}</DialogTitle>
          <DialogDescription>{toDo.text}</DialogDescription>
        </DialogHeader>
        <ToDoForm
          defaultValues={revisionOf(toDo)}
          submitLabel={t('common.save')}
          onCancel={() => onOpenChange(false)}
          onSubmit={async (body) => {
            await revise.mutateAsync({ params: { path: { id: toDo.id } }, body })
            onOpenChange(false)
          }}
        />
      </DialogContent>
    </Dialog>
  )
}
