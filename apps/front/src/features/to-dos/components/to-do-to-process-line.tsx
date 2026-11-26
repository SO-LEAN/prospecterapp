import { CircleCheck } from 'lucide-react'
import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { cn } from '@/shared/lib/utils'
import { Badge } from '@/shared/ui/badge'
import { Button } from '@/shared/ui/button'
import { formatDue } from '../due'
import type { ToDo } from '../queries'
import { useToggle } from '../use-toggle'
import { ReviseToDoDialog } from './revise-to-do-dialog'

type Props = {
  toDo: ToDo
  /** How many days ago it was due. Zero for a to-do due today. */
  daysOverdue: number
  /** Called once the to-do is ticked off. */
  onTickedOff?: () => void
}

const nameOf = ({ name }: NonNullable<ToDo['contact']>) =>
  [name.firstName, name.lastName].filter(Boolean).join(' ')

/**
 * One line of « À traiter »: its box, the to-do's tile, its text, when it is due and for whom, then since when it waits.
 * Once the to-do is ticked off, its text is struck through and the line folds away. Under reduced motion, the line
 * only fades out. The tick leaves « À traiter », so its toast offers to take the tick back. « Reporter » opens its
 * revision, where a shortcut sets its new day. Its links decide which gestures it offers.
 */
export function ToDoToProcessLine({ toDo, daysOverdue, onTickedOff }: Props) {
  const { t } = useTranslation()
  const toggle = useToggle(toDo, { onTickedOff, undoable: true })
  const [postponing, setPostponing] = useState(false)
  const ticked = toggle.checked
  const late = daysOverdue > 0
  const dueAndForWhom = [formatDue(toDo), toDo.contact && nameOf(toDo.contact)].filter(Boolean).join(' · ')

  return (
    <div
      data-fold
      className={cn(
        'grid grid-rows-[1fr] transition-[grid-template-rows,opacity] duration-[260ms] ease-[cubic-bezier(0.34,1.56,0.64,1)] motion-reduce:transition-opacity',
        ticked && 'grid-rows-[0fr] opacity-0 motion-reduce:grid-rows-[1fr]',
      )}
    >
      <div className="min-h-0 overflow-hidden">
        <div
          data-to-do
          data-late={late ? '' : undefined}
          className={cn(
            'flex items-center gap-3 rounded-lg border p-3',
            late && 'border-l-4 border-l-warning',
          )}
        >
          <input type="checkbox" className="size-4 shrink-0 accent-primary" {...toggle} />
          <span className="grid size-8 shrink-0 place-items-center rounded-md bg-warning/15 text-warning">
            <CircleCheck aria-hidden className="size-4" />
          </span>
          <div className="min-w-0 flex-1">
            <p
              className={cn(
                'break-words font-medium text-sm',
                ticked && 'text-muted-foreground line-through',
              )}
            >
              {toDo.text}
            </p>
            <p className="text-muted-foreground text-xs">{dueAndForWhom}</p>
          </div>
          {late ? (
            <Badge variant="outline" className="border-destructive/30 bg-destructive/10 text-destructive">
              {t('toProcess.overdue', { count: daysOverdue })}
            </Badge>
          ) : (
            <Badge variant="outline" className="border-warning/40 bg-warning/15 text-foreground">
              {t('toProcess.forToday')}
            </Badge>
          )}
          {toDo._links.revise && (
            <Button variant="outline" size="sm" onClick={() => setPostponing(true)}>
              {t('toDos.postpone')}
            </Button>
          )}
        </div>
      </div>
      {toDo._links.revise && <ReviseToDoDialog toDo={toDo} open={postponing} onOpenChange={setPostponing} />}
    </div>
  )
}
